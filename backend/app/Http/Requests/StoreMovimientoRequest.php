<?php

namespace App\Http\Requests;

use App\Models\Movimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo_movimiento' => ['required', 'string', Rule::in(Movimiento::TIPOS)],
            'institucion_destino_id' => ['nullable', 'integer', Rule::exists('institutions', 'id')],
            'servicio_destino_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
            'oficina_destino_id' => ['nullable', 'integer', Rule::exists('offices', 'id')],
            'receptor_nombre' => ['nullable', 'string', 'max:255'],
            'receptor_dni' => ['nullable', 'string', 'max:50'],
            'receptor_cargo' => ['nullable', 'string', 'max:255'],
            'fecha_inicio_prestamo' => ['nullable', 'date'],
            'fecha_estimada_devolucion' => ['nullable', 'date', 'after_or_equal:fecha_inicio_prestamo'],
            'observacion' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes(
            ['institucion_destino_id', 'servicio_destino_id', 'oficina_destino_id'],
            ['required'],
            fn () => in_array($this->input('tipo_movimiento'), [
                Movimiento::TIPO_TRASLADO,
                Movimiento::TIPO_TRANSFERENCIA_INTERNA,
                Movimiento::TIPO_TRANSFERENCIA_EXTERNA,
            ], true)
        );

        $validator->sometimes(
            ['receptor_nombre', 'receptor_dni', 'fecha_inicio_prestamo', 'fecha_estimada_devolucion'],
            ['required'],
            fn () => $this->input('tipo_movimiento') === Movimiento::TIPO_PRESTAMO
        );
    }
}
