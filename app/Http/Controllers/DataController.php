<?php

namespace App\Http\Controllers;

use App\Http\Requests\DataStoreRequest;
use App\Models\Data;
use App\Services\AmoCrmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DataController extends Controller
{
    private $amoCrmService;

    public function __construct(AmoCrmService $amoCrmService)
    {
        $this->amoCrmService = $amoCrmService;
    }

    public function store(DataStoreRequest $request)
    {
        Log::info('Полученные данные:', $request->all());

        $data = [
            'name' => $request->get('name'),
            'phone' => $request->get('phone'),
            'city' => $request->get('city'),
            'date' => $request->get('date'),
        ];

        $form = implode(' ', $data);

        try {
            Data::create([
                'hook' => json_encode($data),
                'form' => $form,
                'status_amocrm' => 'unset',
            ]);

            $result = $this->amoCrmService->createContactAndDeal($data);

            if (isset($result['error'])) {
                Log::error('Ошибка при создании сделки в amoCRM: ' . $result['error']);
                return response()->json([
                    'success' => false,
                    'error' => 'Не удалось создать сделку в amoCRM: ' . $result['error'],
                ]);
            }

        } catch (\Exception $th) {
            Log::error('Ошибка при обработке запроса: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Произошла ошибка: ' . $th->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Данные успешно сохранены и сделка создана в amoCRM.',
        ]);
    }
}
