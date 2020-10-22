<?php

/* DESCRIPCIÓN:
 * Este script se utiliza para recibir un archivo de una ordenanza municipal que fue cargada por un usuario del sistema desde un formulario.
 * Se encarga de:
 *   -Controlar que este script se accedió desde el formulario, y no desde otro lugar como la URL.
 *   -Controlar que el archivo recibido tiene formato .PDF
 *   -Que tenga un tamaño adecuado (Menos de 100MB)
 *   -Que no hayan ocurrido errores durante el file upload
 *   -Que la dirección de destino del archivo exista (si no existe, el script la crea)
 *   -Almacenar las ordenanzas según su año, para evitar tener muchos archivos en un mismo directorio
 *   -Verificar si se pudo mover correctamente el archivo a su directorio de destino
 *   -Y verificar si se pudo crear correctamente el registro en la base de datos que se va a relacionar con el registro de la ordenanza.
 * 
 * Todos los posibles errores frenan el script, y redireccionan a otra página, notificándole al usuario cuál fue el error para que pueda contactar al administrador del sistema.
 * El insert a la base de datos se hace utilizando una sentencia parametrizada para evitar SQL injections
 * 
 * -Waldemar Peralta
 */

try {
  session_start();

  // Conexión con la base de datos
  include($_SERVER['DOCUMENT_ROOT'] . "/salm/db/conexion.php");

  // Directorio donde se almacenan los archivos de las ordenanzas.
  $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/salm/ordenanzas/";

  if(!isset($_POST['btnAceptar'])){
    // Si se intenta acceder a este script a través de un método que no sea presionando el botón del formulario
    header("Location: ../ordenanzasListado?msg=direccionIncorrecta");
    exit();
  }

  //Almaceno las variables del form
  $ordId = $_POST['txtId']; //Id de la ordenanza a la que corresponde este archivo
  $nroOrdenanza = $_POST['txtNroOrdenanza']; //Nro de la ordenanza
  $año = $_POST['txtAño']; // Año de la ordenanza
  $usuario = $_SESSION['usrId']; // Usuario que inició sesión en el sistema
  $file = $_FILES['pdfOrdenanza']; // Archivo

  // Variables con información sobre el archivo subido
  $fileName = $file['name']; //nombre del archivo
  $fileTempName = $file['tmp_name']; //ruta temporal
  $fileSize = $file['size']; //tamaño del archivo
  $fileError = $file['error']; //si ocurrió algún error
  $fileType = $file['type']; //tipo del archivo

  /* =============================
   * Inicio de control de errores
   * ============================= */
  $nameFormat = explode('.', $fileName); // Tomar el nombre del archivo (ej: archivo.jpg), y separar las dos palabras en el punto
  $fileExtension = strtolower(end($nameFormat)); // Tomar la segunda palabra y pasarla a letra minúscula (de JPG a jpg)

  $allowedExtensions = array('pdf');  // Array con los tipos de extensión de archivos permitidos

  if(!in_array($fileExtension, $allowedExtensions)){ // Si el archivo no tiene una extensión permitida
    header("Location: ../ordenanzasModificar?id=". $ordId ."&msg=pdfInsertFileExtensionError"); // Extensión de archivo no permitida (.pdf)
    exit();
  }

  if($fileError !== 0){ // Si hubo un error al subir el archivo
    header("Location: ../ordenanzasModificar?id=". $ordId ."&msg=pdfInsertFileUploadError"); // Error al subir el archivo
    exit();
  }

  if($fileSize > 1000000){ // Si el tamaño del archivo es mayor a 1.000.000bytes -> 100megabytes
    header("Location: ../ordenanzasModificar?id=". $ordId ."&msg=pdfInsertFileSizeExceeded"); // Tamaño de archivo excedido
    exit();
  }
  /* =============================
   * Fin de control de errores
   * ============================= */

  // Acá se empieza a subir el archivo
  $nombrePdf = "Ord " . $nroOrdenanza . "-" . $año . " (" . generateRandomString() . ")." . $fileExtension; // Creo un nombre de archivo único -> Ord 134-20 (YzQ).pdf

  $fileRuta = $año . "/" . $nombrePdf; // Ruta del archivo -> 2020/Ord 134-2020 (YzQ).pdf

  //Variable que almacena la ruta de destino donde se va a almacenar el archivo. -> pdfTest/ordenanzas/2020/Ord 134-2020 (YzQ).pdf
  $fileDestination = $upload_dir . $fileRuta;

  //Si no existe la carpeta ordenanzas/año
  if(!is_dir($upload_dir . $año)){
    mkdir($upload_dir . $año, 0777, true); //Crear la carpeta ordenanzas/año con todos los permisos
  }

  //Si la carpeta de destino no existe o no tiene permisos para escribir
  if(!is_dir($upload_dir . $año) || !is_writable($upload_dir . $año)){
    header("Location: ../ordenanzasModificar?id=". $ordId ."&msg=pdfInsertDirectoryError"); //Mostrar mensaje de que no existe el directorio o no se puede escribir
    exit();
  }

  // Mover archivo a la carpeta de destino, y crear el registro en la base de datos.
  $fileWasUploaded = move_uploaded_file($fileTempName, $fileDestination); //Intento mover el archivo
  if(!$fileWasUploaded){ //Si no se pudo mover el archivo
    //Mostrar mensaje con el código de error del archivo
    header("Location: ../ordenanzasModificar?id=". $ordId ."&msg=pdfInsertFileMoveError&errorCode=" . $_FILES["file"]["errorCode"]);
    exit();
  }

  //Intento cargar la ruta y el nombre del archivo PDF en la base de datos
  $resultCargarRuta = $db->execute('UPDATE ordenanzas SET ordNro = ?, ordAño = ?, ordRuta = ?, ordNombrePdf = ?, ordIdUsuario = ? WHERE ordId = ?', array($nroOrdenanza, $año, $fileRuta, $nombrePdf, $usuario, $ordId));

  if($resultCargarRuta){ // Si se insertó exitosamente el registro en la base de datos
    header("Location: ../ordenanzasModificar?id=". $ordId ."&msg=pdfInsertSuccess"); //Se pudo almacenar el nuevo archivo PDF en el servidor, y se cargaron exitosamente la ruta y el nombre del archivo PDF en la base de datos
    exit();
  }else{
    header("Location: ../ordenanzasModificar?id=". $ordId ."&msg=pdfInsertError"); //Se pudo almacenar el archivo PDF en el servidor, pero ocurrió un error al intentar cargar la ruta y el nombre del archivo PDF en la base de datos.
    exit();
  }
} catch (Exception $e) {
  header("Location: ../ordenanzasModificar?id=". $ordId ."&msg=pdfInsertExceptionError&errorCode=" . $e->getCode());
  exit();
}

function generateRandomString(){
  // Función para generar un string con 3 letras aleatorias, que se usa para evitar problemas con el cache del servidor.
	$length = 3;

	$range = array_merge(range('A', 'Z'), range('a', 'z'));

	$out = '';

	for($i = 0; $i < $length; $i++){
		//mt_rand(from, to) => mt_rand(0, <longitud del array $range - 1>) => desde 0, hasta la longitud del array - 1
		$out .= $range[mt_rand(0, count($range) - 1)];
	}	

	return $out;
}