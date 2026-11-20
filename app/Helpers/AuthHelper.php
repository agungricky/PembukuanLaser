<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('userLogin')) {
    function userLogin()
    {
        return Auth::user();
    }
}