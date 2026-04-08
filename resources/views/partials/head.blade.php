<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
<script>
    (() => {
        const savedTheme = localStorage.getItem('app-theme') || 'dark';
        document.documentElement.dataset.theme = savedTheme;
        document.documentElement.classList.toggle('dark', savedTheme === 'dark');
    })();
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
