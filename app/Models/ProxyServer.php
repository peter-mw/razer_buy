<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class ProxyServer extends Model
{
    protected $fillable = [
        'proxy_server_ip',
        'proxy_server_port',
        'is_active',
        'last_used_time',
        'proxy_account_type'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_time' => 'datetime',
        'proxy_account_type' => 'array'
    ];
}
