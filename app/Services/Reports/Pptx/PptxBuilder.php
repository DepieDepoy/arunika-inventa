<?php

namespace App\Services\Reports\Pptx;

use RuntimeException;
use ZipArchive;

/**
 * Pembuat file PowerPoint (.pptx) ringan tanpa library tambahan.
 *
 * Koordinat memakai satuan "px" pada kanvas 1280 x 720 (16:9); 1 px = 9525 EMU.
 * Mendukung: kotak (polos / sudut membulat), teks (multi paragraf, bullet),
 * gambar PNG/JPG, dan tabel.
 */
class PptxBuilder
{
    public const W = 1280;
    public const H = 720;
    private const EMU = 9525;

    /** @var PptxSlide[] */
    private array $slides = [];

    /** @var array<string, string> path => nama media */
    private array $media = [];

    public function __construct(private string $title = 'Vasetra', private string $author = 'Vasetra')
    {
    }

    public function slide(string $background = 'FFFFFF'): PptxSlide
    {
        return $this->slides[] = new PptxSlide($this, count($this->slides) + 1, $background);
    }

    /** @internal Daftarkan file gambar; kembalikan nama media di paket. */
    public function registerMedia(string $path): string
    {
        if (!is_file($path)) {
            throw new RuntimeException("Gambar tidak ditemukan: {$path}");
        }

        return $this->media[$path] ??= 'image' . (count($this->media) + 1) . '.' . strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    public static function emu(float $px): int
    {
        return (int) round($px * self::EMU);
    }

    public static function esc(string $text): string
    {
        // Buang karakter kontrol yang tidak sah di XML.
        $text = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $text) ?? '';

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public function save(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat file PowerPoint.');
        }

        $n = count($this->slides);

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rels([
            ['officeDocument', 'ppt/presentation.xml'],
            ['http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties', 'docProps/core.xml'],
            ['extended-properties', 'docProps/app.xml'],
        ]));
        $zip->addFromString('docProps/core.xml', $this->core());
        $zip->addFromString('docProps/app.xml', $this->app($n));

        $presRels = [['slideMaster', 'slideMasters/slideMaster1.xml']];
        foreach (range(1, max(1, $n)) as $i) {
            if ($i <= $n) {
                $presRels[] = ['slide', "slides/slide{$i}.xml"];
            }
        }
        $presRels[] = ['presProps', 'presProps.xml'];
        $presRels[] = ['viewProps', 'viewProps.xml'];
        $presRels[] = ['theme', 'theme/theme1.xml'];
        $presRels[] = ['tableStyles', 'tableStyles.xml'];

        $zip->addFromString('ppt/presentation.xml', $this->presentation($n));
        $zip->addFromString('ppt/_rels/presentation.xml.rels', $this->rels($presRels));
        $zip->addFromString('ppt/presProps.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:presentationPr ' . self::NS . '/>');
        $zip->addFromString('ppt/viewProps.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:viewPr ' . self::NS . '><p:normalViewPr><p:restoredLeft sz="15620"/><p:restoredTop sz="94660"/></p:normalViewPr><p:gridSpacing cx="76200" cy="76200"/></p:viewPr>');
        $zip->addFromString('ppt/tableStyles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><a:tblStyleLst xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" def="{5C22544A-7EE6-4342-B048-85BDC9FD1C3A}"/>');
        $zip->addFromString('ppt/theme/theme1.xml', $this->theme());
        $zip->addFromString('ppt/slideMasters/slideMaster1.xml', $this->master());
        $zip->addFromString('ppt/slideMasters/_rels/slideMaster1.xml.rels', $this->rels([
            ['slideLayout', '../slideLayouts/slideLayout1.xml'],
            ['theme', '../theme/theme1.xml'],
        ]));
        $zip->addFromString('ppt/slideLayouts/slideLayout1.xml', $this->layout());
        $zip->addFromString('ppt/slideLayouts/_rels/slideLayout1.xml.rels', $this->rels([
            ['slideMaster', '../slideMasters/slideMaster1.xml'],
        ]));

        foreach ($this->slides as $slide) {
            $zip->addFromString("ppt/slides/slide{$slide->number}.xml", $slide->xml());
            $rels = [['slideLayout', '../slideLayouts/slideLayout1.xml']];
            foreach ($slide->images() as $rid => $mediaName) {
                $rels[$rid] = ['image', '../media/' . $mediaName];
            }
            $zip->addFromString("ppt/slides/_rels/slide{$slide->number}.xml.rels", $this->rels($rels, true));
        }

        foreach ($this->media as $path => $name) {
            $zip->addFile($path, 'ppt/media/' . $name);
        }

        $zip->close();
    }

    /* ------------------------------------------------------------------ */

    public const NS = 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"';

    private const REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/';

    /**
     * @param array<int|string, array{0:string,1:string}> $items  [tipe, target]; key string = rId tetap
     */
    private function rels(array $items, bool $keepKeys = false): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $i = 1;
        foreach ($items as $key => [$type, $target]) {
            $id = $keepKeys && is_string($key) ? $key : 'rId' . $i;
            $typeUri = str_starts_with($type, 'http') ? $type : self::REL . $type;
            $xml .= '<Relationship Id="' . $id . '" Type="' . $typeUri . '" Target="' . self::esc($target) . '"/>';
            $i++;
        }

        return $xml . '</Relationships>';
    }

    private function contentTypes(): string
    {
        $pml = 'application/vnd.openxmlformats-officedocument.presentationml.';
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Default Extension="png" ContentType="image/png"/>'
            . '<Default Extension="jpg" ContentType="image/jpeg"/>'
            . '<Default Extension="jpeg" ContentType="image/jpeg"/>'
            . '<Override PartName="/ppt/presentation.xml" ContentType="' . $pml . 'presentation.main+xml"/>'
            . '<Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="' . $pml . 'slideMaster+xml"/>'
            . '<Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="' . $pml . 'slideLayout+xml"/>'
            . '<Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>'
            . '<Override PartName="/ppt/presProps.xml" ContentType="' . $pml . 'presProps+xml"/>'
            . '<Override PartName="/ppt/viewProps.xml" ContentType="' . $pml . 'viewProps+xml"/>'
            . '<Override PartName="/ppt/tableStyles.xml" ContentType="' . $pml . 'tableStyles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';

        foreach ($this->slides as $slide) {
            $xml .= '<Override PartName="/ppt/slides/slide' . $slide->number . '.xml" ContentType="' . $pml . 'slide+xml"/>';
        }

        return $xml . '</Types>';
    }

    private function core(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . self::esc($this->title) . '</dc:title><dc:creator>' . self::esc($this->author) . '</dc:creator><cp:lastModifiedBy>Vasetra</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified></cp:coreProperties>';
    }

    private function app(int $slides): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Vasetra</Application><PresentationFormat>Widescreen</PresentationFormat><Slides>' . $slides . '</Slides></Properties>';
    }

    private function presentation(int $n): string
    {
        $ids = '';
        for ($i = 1; $i <= $n; $i++) {
            $ids .= '<p:sldId id="' . (255 + $i) . '" r:id="rId' . ($i + 1) . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:presentation ' . self::NS . ' saveSubsetFonts="1">'
            . '<p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId1"/></p:sldMasterIdLst>'
            . ($n ? '<p:sldIdLst>' . $ids . '</p:sldIdLst>' : '')
            . '<p:sldSz cx="' . self::emu(self::W) . '" cy="' . self::emu(self::H) . '"/><p:notesSz cx="6858000" cy="9144000"/></p:presentation>';
    }

    private function group(): string
    {
        return '<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>';
    }

    private function master(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:sldMaster ' . self::NS . '>'
            . '<p:cSld><p:bg><p:bgRef idx="1001"><a:schemeClr val="bg1"/></p:bgRef></p:bg><p:spTree>' . $this->group() . '</p:spTree></p:cSld>'
            . '<p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/>'
            . '<p:sldLayoutIdLst><p:sldLayoutId id="2147483649" r:id="rId1"/></p:sldLayoutIdLst></p:sldMaster>';
    }

    private function layout(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:sldLayout ' . self::NS . ' type="blank" preserve="1">'
            . '<p:cSld name="Blank"><p:spTree>' . $this->group() . '</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sldLayout>';
    }

    private function theme(): string
    {
        $c = fn ($name, $hex) => "<a:{$name}><a:srgbClr val=\"{$hex}\"/></a:{$name}>";
        $fill = '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>';
        $ln = '<a:ln w="9525"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Vasetra">'
            . '<a:themeElements><a:clrScheme name="Vasetra">'
            . '<a:dk1><a:sysClr val="windowText" lastClr="000000"/></a:dk1><a:lt1><a:sysClr val="window" lastClr="FFFFFF"/></a:lt1>'
            . $c('dk2', '0B2447') . $c('lt2', 'F4F7FB') . $c('accent1', '1F5FBF') . $c('accent2', '14B8A6') . $c('accent3', 'EB6834')
            . $c('accent4', 'EDA100') . $c('accent5', '6250D6') . $c('accent6', 'E34948') . $c('hlink', '1F5FBF') . $c('folHlink', '6250D6')
            . '</a:clrScheme><a:fontScheme name="Vasetra"><a:majorFont><a:latin typeface="Calibri"/><a:ea typeface=""/><a:cs typeface=""/></a:majorFont>'
            . '<a:minorFont><a:latin typeface="Calibri"/><a:ea typeface=""/><a:cs typeface=""/></a:minorFont></a:fontScheme>'
            . '<a:fmtScheme name="Vasetra"><a:fillStyleLst>' . $fill . $fill . $fill . '</a:fillStyleLst>'
            . '<a:lnStyleLst>' . $ln . $ln . $ln . '</a:lnStyleLst>'
            . '<a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle><a:effectStyle><a:effectLst/></a:effectStyle><a:effectStyle><a:effectLst/></a:effectStyle></a:effectStyleLst>'
            . '<a:bgFillStyleLst>' . $fill . $fill . $fill . '</a:bgFillStyleLst></a:fmtScheme></a:themeElements>'
            . '<a:objectDefaults/><a:extraClrSchemeLst/></a:theme>';
    }
}
