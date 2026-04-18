<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

interface BaseApiInterface
{
    public function index(Request $request);
    public function store(Request $request);
    public function show(Model $model);
    public function update(Request $request, Model $model);
    public function destroy(Model $model);
}
