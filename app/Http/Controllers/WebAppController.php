<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WebAppController extends Controller
{
    /**
     * The Expo web build lives in public/web-app (built by `npm run build:web-app`
     * in app/). Real files there are served directly by the web server; this
     * only catches the app's client-side routes (/web-app/party/12, …) and hands
     * back the SPA shell so a refresh or shared link still opens the app.
     */
    public function __invoke(?string $path = null): BinaryFileResponse
    {
        // A missing .js/.wasm/.png must 404, not get index.html back as "JS".
        abort_if($path !== null && pathinfo($path, PATHINFO_EXTENSION) !== '', 404);

        $index = public_path('web-app/index.html');
        abort_unless(is_file($index), 404, 'The web app has not been built yet.');

        return response()->file($index, [
            'Content-Type' => 'text/html; charset=UTF-8',
            // Always revalidate so a fresh build is picked up immediately.
            'Cache-Control' => 'no-cache',
        ]);
    }
}
