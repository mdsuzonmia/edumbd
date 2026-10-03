<?php

namespace App\Controllers;

class Language extends BaseController
{
    public function switch(string $locale)
    {
        $supported = config('App')->supportedLocales;
        if (! in_array($locale, $supported, true)) {
            $locale = 'en';
        }

        session()->set([
            'app_locale'     => $locale,
            'locale_version' => 2,
        ]);
        session()->remove('locale');
        $this->request->setLocale($locale);
        service('language')->setLocale($locale);

        return redirect()->back();
    }
}
