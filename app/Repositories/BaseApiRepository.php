<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class BaseApiRepository
{

    // protected $model;
    // protected $name;
    // public function __construct(Model $model, $name = 'Model')
    // {
    //     $this->model = $model;
    //     $this->name = $name;
    // }

    // public function getQueryAttributes(Request $request, $model)
    // {
    //     $id = $request->query('id');
    //     if ($id) {
    //         $model = $model->where('id', $id);
    //     }

    //     return $model;
    // }

    public function index(Request $request, Model $model, $name = 'Model')
    {


        $dataQuery = $model::query();




        $data = $dataQuery->get();

        return response()->json([
            "success" => $data->count() > 0,
            "message" => $this->name . ' fetched successfully',
            "data" => $data,
        ], 200);
    }

    public function store(Request $request)
    {


        $data = $this->model::create($request->all());

        return response()->json([
            "success" => $data->wasCreated(),
            "message" => $this->name . ' created successfully',
            "data" => $data,
        ], 201);
    }

    public function show(Model $model)
    {
        return response()->json([
            "success" => true,
            "message" => $this->name . ' fetched successfully',
            "data" => $model,
        ], 200);
    }

    public function update(Request $request, Model $model)
    {


        $model->update($request->all());

        $message = $model->wasChanged() ? $this->name . ' updated successfully' : $this->name . ' not found';

        return response()->json([
            "success" => $model->wasChanged(),
            "message" => $message,
            "data" => $model,
        ], 200);
    }

    public function destroy(Model $model)
    {
        $model->delete();
        $message = $model->trashed() ? $this->name . ' deleted successfully' : $this->name . ' not found';
        return response()->json([
            "success" => $model->trashed(),
            "message" => $message,
            "data" => $model,
        ], 200);
    }
}
