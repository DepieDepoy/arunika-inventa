{{-- Google Analytics 4 (opsional): hanya dipasang bila Measurement ID diisi di Backoffice > Analitik. --}}
@php $ga4Id = \App\Services\Analytics\AnalyticsSettings::ga4Id(); @endphp
@if($ga4Id)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', @json($ga4Id));
</script>
@endif
