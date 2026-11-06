<?php

namespace App\Services\Reports\Pptx;

/**
 * Satu slide. Semua posisi dalam px kanvas 1280 x 720.
 */
class PptxSlide
{
    private string $shapes = '';
    private int $nextId = 2;

    /** @var array<string, string> rId => nama media */
    private array $images = [];

    public function __construct(private PptxBuilder $deck, public readonly int $number, private string $background)
    {
    }

    /**
     * Kotak isi warna. $radius 0..0.5 (proporsi sudut membulat).
     */
    public function rect(float $x, float $y, float $w, float $h, string $fill, float $radius = 0, ?string $line = null, float $alpha = 1): self
    {
        $geom = $radius > 0
            ? '<a:prstGeom prst="roundRect"><a:avLst><a:gd name="adj" fmla="val ' . (int) round($radius * 100000) . '"/></a:avLst></a:prstGeom>'
            : '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>';

        $alphaXml = $alpha < 1 ? '<a:alpha val="' . (int) round($alpha * 100000) . '"/>' : '';
        $lineXml = $line ? '<a:ln w="9525"><a:solidFill><a:srgbClr val="' . $line . '"/></a:solidFill></a:ln>' : '<a:ln><a:noFill/></a:ln>';

        return $this->add('<p:sp><p:nvSpPr><p:cNvPr id="' . $this->id() . '" name="Shape ' . $this->nextId . '"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr>'
            . '<p:spPr>' . $this->xfrm($x, $y, $w, $h) . $geom . '<a:solidFill><a:srgbClr val="' . $fill . '">' . $alphaXml . '</a:srgbClr></a:solidFill>' . $lineXml . '</p:spPr>'
            . '<p:txBody><a:bodyPr/><a:lstStyle/><a:p><a:endParaRPr lang="id-ID"/></a:p></p:txBody></p:sp>');
    }

    /**
     * Teks. $text: string (baris dipisah \n = paragraf) atau array paragraf.
     * Opsi: size (pt), color, bold, align (l|ctr|r), valign (t|ctr|b), bullet (bool), italic, spacing (pt antar paragraf).
     */
    public function text(float $x, float $y, float $w, float $h, string|array $text, array $o = []): self
    {
        $size = (int) round(($o['size'] ?? 14) * 100);
        $color = $o['color'] ?? '24364D';
        $bold = !empty($o['bold']) ? ' b="1"' : '';
        $italic = !empty($o['italic']) ? ' i="1"' : '';
        $align = $o['align'] ?? 'l';
        $anchor = $o['valign'] ?? 't';
        $bullet = !empty($o['bullet']);
        $spacing = isset($o['spacing']) ? '<a:spcBef><a:spcPts val="' . (int) round($o['spacing'] * 100) . '"/></a:spcBef>' : '';

        $paras = is_array($text) ? $text : explode("\n", $text);
        $body = '';
        foreach ($paras as $para) {
            $ppr = $bullet
                ? '<a:pPr marL="228600" indent="-228600" algn="' . $align . '">' . $spacing . '<a:buClr><a:srgbClr val="' . ($o['bulletColor'] ?? '14B8A6') . '"/></a:buClr><a:buFont typeface="Arial"/><a:buChar char="&#8226;"/></a:pPr>'
                : '<a:pPr algn="' . $align . '">' . $spacing . '<a:buNone/></a:pPr>';
            $body .= '<a:p>' . $ppr . '<a:r><a:rPr lang="id-ID" sz="' . $size . '"' . $bold . $italic . ' dirty="0"><a:solidFill><a:srgbClr val="' . $color . '"/></a:solidFill>'
                . '<a:latin typeface="Calibri"/><a:cs typeface="Calibri"/></a:rPr><a:t>' . PptxBuilder::esc((string) $para) . '</a:t></a:r></a:p>';
        }

        return $this->add('<p:sp><p:nvSpPr><p:cNvPr id="' . $this->id() . '" name="Text ' . $this->nextId . '"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>'
            . '<p:spPr>' . $this->xfrm($x, $y, $w, $h) . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/></p:spPr>'
            . '<p:txBody><a:bodyPr wrap="square" lIns="0" tIns="0" rIns="0" bIns="0" anchor="' . $anchor . '"><a:normAutofit/></a:bodyPr><a:lstStyle/>' . $body . '</p:txBody></p:sp>');
    }

    public function image(string $path, float $x, float $y, float $w, float $h): self
    {
        $media = $this->deck->registerMedia($path);
        $rid = 'rIdImg' . (count($this->images) + 1);
        $this->images[$rid] = $media;

        return $this->add('<p:pic><p:nvPicPr><p:cNvPr id="' . $this->id() . '" name="Picture ' . $this->nextId . '"/><p:cNvPicPr><a:picLocks noChangeAspect="1"/></p:cNvPicPr><p:nvPr/></p:nvPicPr>'
            . '<p:blipFill><a:blip r:embed="' . $rid . '"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>'
            . '<p:spPr>' . $this->xfrm($x, $y, $w, $h) . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></p:spPr></p:pic>');
    }

    /**
     * Tabel. $rows[0] = judul kolom. Opsi: widths (px per kolom), size, rowHeight, align (per kolom: l|ctr|r), header (warna).
     */
    public function table(float $x, float $y, float $w, array $rows, array $o = []): self
    {
        if (!$rows) {
            return $this;
        }

        $cols = count($rows[0]);
        $widths = $o['widths'] ?? array_fill(0, $cols, $w / $cols);
        $rowH = $o['rowHeight'] ?? 30;
        $size = (int) round(($o['size'] ?? 11) * 100);
        $aligns = $o['align'] ?? [];
        $header = $o['header'] ?? '0B2447';

        $grid = '';
        foreach ($widths as $cw) {
            $grid .= '<a:gridCol w="' . PptxBuilder::emu($cw) . '"/>';
        }

        $trs = '';
        foreach (array_values($rows) as $ri => $row) {
            $isHead = $ri === 0;
            $fill = $isHead ? $header : ($ri % 2 === 0 ? 'F6F8FB' : 'FFFFFF');
            $color = $isHead ? 'FFFFFF' : '24364D';
            $tcs = '';
            foreach (array_values($row) as $ci => $cell) {
                $al = $aligns[$ci] ?? 'l';
                $tcs .= '<a:tc><a:txBody><a:bodyPr/><a:lstStyle/><a:p><a:pPr algn="' . $al . '"/><a:r><a:rPr lang="id-ID" sz="' . $size . '"' . ($isHead ? ' b="1"' : '') . ' dirty="0">'
                    . '<a:solidFill><a:srgbClr val="' . $color . '"/></a:solidFill><a:latin typeface="Calibri"/></a:rPr><a:t>' . PptxBuilder::esc((string) $cell) . '</a:t></a:r></a:p></a:txBody>'
                    . '<a:tcPr marL="91440" marR="91440" marT="45720" marB="45720" anchor="ctr">'
                    . '<a:lnL w="0"><a:noFill/></a:lnL><a:lnR w="0"><a:noFill/></a:lnR><a:lnT w="0"><a:noFill/></a:lnT>'
                    . '<a:lnB w="6350"><a:solidFill><a:srgbClr val="DCE3EC"/></a:solidFill></a:lnB>'
                    . '<a:solidFill><a:srgbClr val="' . $fill . '"/></a:solidFill></a:tcPr></a:tc>';
            }
            $trs .= '<a:tr h="' . PptxBuilder::emu($rowH) . '">' . $tcs . '</a:tr>';
        }

        $h = $rowH * count($rows);

        return $this->add('<p:graphicFrame><p:nvGraphicFramePr><p:cNvPr id="' . $this->id() . '" name="Table ' . $this->nextId . '"/><p:cNvGraphicFramePr><a:graphicFrameLocks noGrp="1"/></p:cNvGraphicFramePr><p:nvPr/></p:nvGraphicFramePr>'
            . '<p:xfrm><a:off x="' . PptxBuilder::emu($x) . '" y="' . PptxBuilder::emu($y) . '"/><a:ext cx="' . PptxBuilder::emu($w) . '" cy="' . PptxBuilder::emu($h) . '"/></p:xfrm>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/table"><a:tbl><a:tblPr firstRow="1" bandRow="1"/><a:tblGrid>' . $grid . '</a:tblGrid>' . $trs . '</a:tbl></a:graphicData></a:graphic></p:graphicFrame>');
    }

    /** @return array<string, string> */
    public function images(): array
    {
        return $this->images;
    }

    public function xml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:sld ' . PptxBuilder::NS . '>'
            . '<p:cSld><p:bg><p:bgPr><a:solidFill><a:srgbClr val="' . $this->background . '"/></a:solidFill><a:effectLst/></p:bgPr></p:bg><p:spTree>'
            . '<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>'
            . $this->shapes . '</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sld>';
    }

    private function add(string $xml): self
    {
        $this->shapes .= $xml;

        return $this;
    }

    private function id(): int
    {
        return $this->nextId++;
    }

    private function xfrm(float $x, float $y, float $w, float $h): string
    {
        return '<a:xfrm><a:off x="' . PptxBuilder::emu($x) . '" y="' . PptxBuilder::emu($y) . '"/><a:ext cx="' . PptxBuilder::emu(max(1, $w)) . '" cy="' . PptxBuilder::emu(max(1, $h)) . '"/></a:xfrm>';
    }
}
