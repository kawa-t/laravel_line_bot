<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineFriend extends Model
{
  //SQLのテーブル名を指定
  protected $table = 'line_friends';

  //DBに保存する値はFillableに指定
  protected $fillable = ['line_id', 'display_name',];
}
