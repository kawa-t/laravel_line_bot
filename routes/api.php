<?php

//タイプを入力
Route::post('/parrot', 'LineBotTypeController@input_type');

//ポケモンのnameを入力
Route::post('/pokename', 'LineBotPokenameController@input_pokemon');