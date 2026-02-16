<?php

namespace app\controller;

use support\Request;

class IndexController
{
    public function index(Request $request)
    {
        return <<<EOF
<h1>Hello World</h1>
EOF;
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

    public function test(Request $request)
    {
        return json(['message' => 'Это тестовый роут!', 'time' => date('Y-m-d H:i:s')]);
    }

}
