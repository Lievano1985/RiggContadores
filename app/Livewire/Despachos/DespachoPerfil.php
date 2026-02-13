<?php
namespace App\Livewire\Despachos;

use App\Models\Despacho;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use App\Services\GeneradorObligaciones;
use Carbon\Carbon;
class DespachoPerfil extends Component
{
    public $nombre, $rfc, $correo_contacto, $telefono_contacto, $drive_folder_id, $politica_almacenamiento;
    public $admin_name, $admin_email, $admin_password;
    public bool $mostrarAdvertencia = false;
    public string $politica_original;
    public function mount()
    {
        $user = Auth::user();
        $despacho = $user->despacho;

        $this->nombre = $despacho->nombre;
        $this->rfc = $despacho->rfc;
        $this->correo_contacto = $despacho->correo_contacto;
        $this->telefono_contacto = $despacho->telefono_contacto;
        $this->drive_folder_id = $despacho->drive_folder_id;
        $this->politica_almacenamiento = $despacho->politica_almacenamiento;
        $this->politica_original = $despacho->politica_almacenamiento;
        $this->admin_name = $user->name;
        $this->admin_email = $user->email;
    }



    public function rules()
    {
        return [
            'nombre' => 'required|string|max:255',
            'rfc' => 'required|string|max:13',
            'correo_contacto' => 'nullable|email',
            'telefono_contacto' => 'nullable|string|max:20',
            'drive_folder_id' => 'nullable|string|max:255',
            'politica_almacenamiento' => 'required|in:storage_only,drive_only,both',

            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email,' . Auth::id(),
            'admin_password' => 'nullable|string|min:8',
        ];
    }

    public function actualizar()
    {
        $this->validate();

        $user = Auth::user();
        $despacho = $user->despacho;

        $despacho->update([
            'nombre' => $this->nombre,
            'rfc' => $this->rfc,
            'correo_contacto' => $this->correo_contacto,
            'telefono_contacto' => $this->telefono_contacto,
            'drive_folder_id' => $this->drive_folder_id,
            'politica_almacenamiento' => $this->politica_almacenamiento,
        ]);

        $user->name = $this->admin_name;
        $user->email = $this->admin_email;
        if ($this->admin_password) {
            $user->password = Hash::make($this->admin_password);
        }
        $user->save();

        $this->politica_original = $this->politica_almacenamiento;

        $this->dispatch(
            'notify',
            message: 'Información actualizada correctamente.'
        );
    }

    public function updatedPoliticaAlmacenamiento($value)
    {
        $this->mostrarAdvertencia = $value !== $this->politica_original;
    }
    public function render()
    {
        return view('livewire.despachos.despacho-perfil');
    }
    public function ejecutarGeneracionMensual()
{
    try {
/*         $fecha = now()->startOfMonth()->setYear(2026)->setMonth(1);
 */
        $resultado = app(GeneradorObligaciones::class)
            ->generarParaPeriodo(Carbon::now());
/*             ->generarParaPeriodo($fecha);
 */
        if (($resultado['generadas'] ?? 0) > 0) {
           
            $this->dispatch(
                'notify',
                message: "Se generaron {$resultado['generadas']} obligaciones del periodo actual."
            );
        } else {
            dd($resultado);

            $this->dispatch(
                'notify',
                message: 'El periodo actual ya estaba completamente generado. No se realizaron cambios.'

            );
        }

    } catch (\Throwable $e) {
        report($e);
       

        $this->dispatch(
            'notify',
            message:'Ocurrió un error al ejecutar la generación de obligaciones.'
        );
    }
}
}
