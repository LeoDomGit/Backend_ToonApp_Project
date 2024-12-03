<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Support\Str;
trait HasCrud
{
    protected $model;
    protected $view;
    protected $data;
    protected $request;
    public function index()
    {
        return Inertia::render($this->view,$this->data);
    }

    /**
     * Store a new model instance with validation.
     *
     * @param FormRequest $request  Specific request class for validation
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(FormRequest $request = null)
    {
        $request = $request ?: request();
        if ($request instanceof FormRequest) {
            $data = $request->validated();
        } else {
            $data = $request->all();
        }
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $item = $this->model::create($data);
        $data = $this->model::all();

        return response()->json(['check' => true, 'data' => $data], 201);
    }

    /**
     * Show a specific model instance.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $item = $this->model::find($id);
        if (!$item) {
            return response()->json(['error' => 'Resource not found'], 404);
        }
        return response()->json($item, 200);
    }

    /**
     * Update a specific model instance with validation.
     *
     * @param FormRequest $request  Specific request class for validation
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Delete a specific model instance.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $item = $this->model::find($id);
        if (!$item) {
            return response()->json(['error' => 'Resource not found'], 404);
        }
        $item->delete();
        $data=$this->model::all();
        return response()->json(['check'=>true,'data'=>$data], 201);
    }
}
