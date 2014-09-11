<?php

namespace Frontend\Controller;

use Core\Http\Request;
use Core\Http\Response;

class Security
{
    public function signin(Request $request, Response $response)
    {

        if($request->isPost())
        {
            $data = $request->getData('signin');

            return print_r($data, true);
        }

        return 'ciao';
    }

    public function signout()
    {

    }
}