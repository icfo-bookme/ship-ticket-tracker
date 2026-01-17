<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcelSetting extends Model
{
    use HasFactory;

    protected $table = 'excel_settings';

    protected $fillable = [
        'spreadsheetId',
        'range',
    ];
}
