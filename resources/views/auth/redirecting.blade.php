<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url={{ $target }}">
    <title>Redirecting...</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8fafc; color: #0f172a; display: grid; min-height: 100vh; place-items: center; margin: 0;">
    <main style="text-align: center; padding: 2rem;">
        <p style="margin: 0 0 0.75rem; font-size: 1rem;">Redirecting...</p>
        <p style="margin: 0; font-size: 0.95rem;">
            If nothing happens, <a href="{{ $target }}">continue here</a>.
        </p>
    </main>
    <script>
        window.location.replace(@json($target));
    </script>
</body>
</html>
