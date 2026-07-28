<?php

namespace App\Http\Controllers;

use App\Support\LandingChrome;
use Illuminate\Contracts\View\View;

class LegalPageController extends Controller
{
    public function privacy(): View
    {
        return $this->render('privacy');
    }

    public function terms(): View
    {
        return $this->render('terms');
    }

    private function render(string $view): View
    {
        $locale = LandingChrome::resolveLocale();
        $sections = LandingChrome::sections();

        return view($view, [
            'locale' => $locale,
            'siteSettings' => LandingChrome::siteSettings($sections),
            'sectionVisibility' => LandingChrome::sectionVisibility($sections),
        ]);
    }
}
