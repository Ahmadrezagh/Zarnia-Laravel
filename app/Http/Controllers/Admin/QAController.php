<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QA\StoreQuestionRequest;
use App\Http\Requests\Admin\QA\UpdateQuestionRequest;
use App\Models\Permission;
use App\Models\QA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class QAController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $qas = QA::query()->latest()->paginate();
        return view('admin.qas.index', compact('qas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionRequest $request)
    {
        QA::query()->create($request->validated());
        return response()->json();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionRequest $request, QA $qa)
    {
        $qa->update($request->validated());
        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QA $qa)
    {
        $qa->delete();
        return response()->json();
    }


    public function table()
    {
        $qas = qa::query()->paginate();


        // Loop through users and render the Blade string for each
        foreach ($qas as $qa) {
            $slotContent .= Blade::render(
                <<<'BLADE'
             <x-modal.destroy id="modal-destroy-{{$qa->id}}" title="حذف نقش" action="{{route('qas.destroy', $qa->id)}}" title="{{$qa->title}}" />

                <x-modal.update id="modal-edit-{{$qa->id}}" title="ویرایش نقش" action="{{route('qas.update',$qa->id)}}" >
                     <x-form.input title="سوال"  name="question" :value="$qa->question" />
                    <x-form.input title="جواب"  name="answer" :value="$qa->answer" />
                </x-modal.update>
            BLADE,
                ['qa' => $qa, 'permissions']
            );
        }

        return view('components.table', [
            'id' => 'qas-table',
            'columns' => [
                ['label' => 'نام', 'key' => 'title', 'type' => 'text'],
            ],
            'url' => route('table.qas'),
            'items' => $qas,
            'actions' => [
                ['label' => 'ویرایش', 'type' => 'modal-edit'],
                ['label' => 'حذف', 'type' => 'modal-destroy']
            ],
            'slot' => $slotContent
        ]);
    }
}
