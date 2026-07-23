{{-- PWA head tags. Theme-color follows current theme (set by theme-init before paint). --}}
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff">
<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#0e1514">
<meta name="application-name" content="NirwanaHRIS">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="NirwanaHRIS">
<link rel="icon" type="image/png" href="/img/android-chrome-192x192.png">
<link rel="apple-touch-icon" href="/img/android-chrome-192x192.png">

{{-- Splash iOS. iOS tak membaca manifest untuk splash, hanya tag ini, dan media
     query harus cocok persis dengan device. Berkas dibuat `php artisan pwa:splash`.
     Lebar/tinggi di media query = piksel CSS, nama berkas = piksel device — beda,
     jangan disamakan. iPhone di luar daftar dapat latar polos #0c1312 tanpa logo. --}}
<link rel="apple-touch-startup-image" href="/img/splash/splash-750x1334.png"
      media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/img/splash/splash-828x1792.png"
      media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/img/splash/splash-1170x2532.png"
      media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/img/splash/splash-1179x2556.png"
      media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/img/splash/splash-1284x2778.png"
      media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
<link rel="apple-touch-startup-image" href="/img/splash/splash-1290x2796.png"
      media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)">
