<?php

namespace App\Services;

use Exception;
use Log;

class AmoCrmService
{
    private $subdomain;
    private $accessToken;

    public function __construct()
    {
        $this->subdomain = config('amocrm.subdomain');
        $this->accessToken = config('amocrm.access_token');
    }

    public function createContactAndDeal(array $data)
    {
        $fullName = $data['name'];
        $phoneNumber = $data['phone'];
        $cityTag = $data['city'];
        $date = $data['date'];

        try {
            $contactId = $this->addContact($fullName, $phoneNumber);
            if (!$contactId) {
                throw new Exception('Не удалось создать контакт.');
            }

            $leadName = 'Сделка для ' . $fullName;
            Log::info("Создание сделки с данными: ", ['leadName' => $leadName]);

            $leadsData = [
                [
                    'name' => $leadName,
                    'price' => 20000,
                    'tags_to_add' => [
                        ['name' => $cityTag],
                    ],
                    '_embedded' => [
                        'contacts' => [
                            ['id' => $contactId]
                        ]
                    ]
                ],
            ];

            $leads = $this->addLead($leadsData);

            if (!$leads) {
                throw new Exception('Не удалось создать сделку.');
            }

            return ['message' => 'Сделка и контакт успешно созданы', 'leads' => $leads];
        } catch (Exception $e) {
            // Логирование ошибки
            Log::error('Ошибка при создании сделки: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    private function addContact($fullName, $phoneNumber)
    {
        $url = "https://{$this->subdomain}.amocrm.ru/api/v4/contacts";

        // Формируем данные для создания контакта
        $contactData = [
            [
                'first_name' => $fullName,
                'custom_fields_values' => [
                    [
                        'field_id' => 316445, // Используем правильный ID для телефона
                        'values' => [
                            [
                                'value' => $phoneNumber,
                                'enum_id' => 275299 // Указываем, что это домашний номер
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $options = [
            'http' => [
                'header'  => "Authorization: Bearer {$this->accessToken}\r\n" .
                    "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($contactData),
                'ignore_errors' => true,
            ],
        ];

        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === FALSE) {
            $error = error_get_last();
            Log::error("Ошибка при добавлении контакта: " . $error['message']);
            return null;
        }

        $response_data = json_decode($response, true);

        if (isset($response_data['_embedded']['contacts'][0]['id'])) {
            return $response_data['_embedded']['contacts'][0]['id']; // Возвращаем ID контакта
        } else {
            Log::error("Ошибка при создании контакта: " . json_encode($response_data));
            return null;
        }
    }



    private function addLead(array $leadsData)
    {
        Log::info("Вызов метода addLead с данными: ", ['leadsData' => $leadsData]);

        $url = "https://{$this->subdomain}.amocrm.ru/api/v4/leads";

        $options = [
            'http' => [
                'header'  => "Authorization: Bearer {$this->accessToken}\r\n" .
                    "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($leadsData),
                'ignore_errors' => true,
            ],
        ];

        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === FALSE) {
            $error = error_get_last();
            Log::error("Ошибка при выполнении запроса: " . $error['message']);
            return null;
        }

        $response_data = json_decode($response, true);
        Log::info("Ответ от API amoCRM: ", ['response' => $response_data]);

        if (isset($response_data['_embedded']['leads'])) {
            return $response_data['_embedded']['leads'];
        } else {
            Log::error("Ошибка при добавлении сделок: " . json_encode($response_data));
            return null;
        }
    }
}
