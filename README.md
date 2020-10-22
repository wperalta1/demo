# demo Dovada
Este script se utiliza para recibir un archivo de una ordenanza municipal que fue cargada por un usuario del sistema desde un formulario.
  Se encarga de:
    -Controlar que este script se accedió desde el formulario, y no desde otro lugar como la URL.
    -Controlar que el archivo recibido tiene formato .PDF
    -Que tenga un tamaño adecuado (Menos de 100MB)
    -Que no hayan ocurrido errores durante el file upload
    -Que la dirección de destino del archivo exista (si no existe, el script la crea)
    -Almacenar las ordenanzas según su año, para evitar tener muchos archivos en un mismo directorio
    -Verificar si se pudo mover correctamente el archivo a su directorio de destino
    -Y verificar si se pudo crear correctamente el registro en la base de datos que se va a relacionar con el registro de la ordenanza.
  
  Todos los posibles errores frenan el script, y redireccionan a otra página, notificándole al usuario cuál fue el error para que pueda contactar al administrador del sistema.
  El insert a la base de datos se hace utilizando una sentencia parametrizada para evitar SQL injections
  
 Video de demostración del script funcionando: (es el modal donde se carga el .pdf)
 
 https://youtu.be/5SKKPJDY-U0
  
  -Waldemar Peralta
