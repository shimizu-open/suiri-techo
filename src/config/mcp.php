<?php

/*
| AI とつなぐ窓口（laravel/mcp）の設定。くわしい案内は config/ai_connect.php（窓口の説明・道具の説明文・画面の言葉）。
| 元の設定は「どこへでも戻せる」（redirect_domains が '*'）ので、使う AI のサービスの住所だけに絞る。
*/

return [

    /*
    | AI が自分で登録する（動的クライアント登録・POST /oauth/register）ときに、許可のあとに戻る先として認める住所。
    | 前方一致で比べ、末尾に / を足すので https://chatgpt.com.evil.example のような住所は通らない。
    | ChatGPT：https://chatgpt.com/connector/oauth/… など。Claude：https://claude.ai/api/mcp/auth_callback（claude.com も念のため）。
    | よその窓口から来た人の Origin を断るときにも、この一覧を「AI のサービスの住所」として使う（RejectForeignOrigin）
    */
    'redirect_domains' => [
        'https://chatgpt.com',
        'https://claude.ai',
        'https://claude.com',
    ],

    // 手元のアプリ専用の住所の形（cursor:// など）は認めない
    'custom_schemes' => [],

    // 通行証を出す係の名前（RFC 8414 の issuer）。null ならこのアプリの住所（url('/')）
    'authorization_server' => null,

    // 道具を探す機能の上限（laravel/mcp の既定のまま。推理手帳の窓口では使っていない）
    'tool_search' => [
        'max_tool_calls' => 10,
        'max_output_bytes' => 65_536,
    ],

    // 推理手帳からよその MCP の窓口にはつながない
    'clients' => [],

];
