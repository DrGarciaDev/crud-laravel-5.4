<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;


class RespaldosController extends Controller
{
    public $process = NULL;

    public function realizarTransferenciaClouds(Request $request){
        //si la carpeta no existe se crea en el directorio
        
        // Config::set('laravel-backup.notifications.mail',array('to'=>$configuracion_respaldo->email));
        $carpeta                        = '/respaldos-crud';
        // $archivosParaBorrar             = Storage::disk('public')->files($carpeta);
        //vaciar la carpeta antes de realizar el respaldo
        // $this->borraArchivosDeRespaldo($request, $carpeta);
        // $this->realizarBorradoDeArchivos($request, $archivosParaBorrar);
        // Realizamos el respaldo del Zip y la base de datos
        try {
            Artisan::call('backup:run');

        } catch (\Throwable $th) {
            print_r('<pre>');
            print_r('NOO ENTRA A ARTISAN RUN');
            echo ($th);
            
            if($request->ajax()){
                return "-1|No se pudo realizar el respaldo de la base de datos ".$th;
            }else{
                // Es cronjob, envío corre electronico y paro ejecución
                // Email::enviarEmailDeRespaldo('Error al respaldar','No se pudo generar el archivo de respaldo','error');
            }
        }
        $archivos                       = Storage::disk('local')->files($carpeta);
        // si existe el respaldo realizar la conexion ftp, tiene que ser el unico archivo en la carpeta
        if (count($archivos)>0) {
            foreach($archivos as $archivo){
                $nombre_de_archivo = substr($archivo, -23);

		        // print_r('<pre>');
                $archivo = str_replace('/', '\\', $archivo);
                // print_r('<br>');
                if( ! Storage::disk('ftp')->exists($nombre_de_archivo)) {
                    // print_r('NO EXISTE EN FTP'. $nombre_de_archivo);
                    // con get obtiene el archivo físico
                    $file_content = Storage::disk('local')->get($archivo);
                    // parametros del storage disk put: como se llamará el archivo, el contenido de este, visibilidad
                    Storage::disk('ftp')->put( $nombre_de_archivo, $file_content);
                    Storage::disk('google')->put( $nombre_de_archivo, $file_content);
                    // print_r('DISK GOOGLE');
                    // print_r('<br>');
                    // print_r(Storage::disk('google')->files());
                    // print_r('DISK FTP');
                    // print_r('<br>');
                    // print_r(Storage::disk('ftp')->files());
                }
            }
        }
    }
}
