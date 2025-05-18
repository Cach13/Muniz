Proyecto de cronograma para Muñiz:
Consiste en distintas pantallas con funciones como inicio de sesion, cambiar contraseñas, eliminar, registrar programas de estudio, dosificar horas de estudio a lo largo
del semestre y genear reportes

RELACIONES IMPORTANTES BD:

Un admin crea un semestre, los días no hábiles y los programas,

Un programa tiene varias unidades, y cada unidad tiene varios temas,

Un usuario (alumno) puede ver los programas y definir horas estimadas para cada tema y su disponibilidad diaria,

El sistema usa esos datos para calcular la dosificación, evitando feriados y días de examen.

Cálculos en Backend:

Tomar las fechas del semestre (fecha_inicio a fecha_fin),

Excluir los DiasNoHabiles y fecha_evaluacion de Unidades,

Tomar disponibilidad del alumno (Disponibilidad) para saber en qué fechas y horas puede estudiar,

Distribuir las horas_estimadas de cada tema entre los días disponibles antes de su evaluación,

Guardar resultado en Dosificacion.

-Primer commit:
Maquetacion de todas las pantallas, asi como su base de datos

-Segundo commit: Se integra funcionalidades php (sin testear) en archivos de la carpeta admin,se añadio config.php que conecta con la base de datos, se agrega Login.php que verifica al usuario con lo que se manda desde index (que fue cambiado) y cambia texto plano en contraseña para hashearlo, falta cambiar para no poder acceder a ninguna pantalla sin iniciar sesion y cambio a la estructura de la bd en BD.txt

-Tercer commit: Se integran funcionalidades completas en toda la carpeta admin y quedan funcionales asi como con todas sus alertas correspondientes, se agrega un pequeño estilo en acciones.css para dar idea de la implementacion para la visualizacion de las alertas al usuario, se agrego una nueva tabla en la base de datos y se quito "horas_estimadas" de la tabla temas (Listo para testear profundamente)

-Cuarto commit: Se agregan 3 scripts nuevos para la funcionalidad del usuario con su correspondiente funcionalidad (listos para testear profundamente) dejando listo el apartado de usuario,se agrega un pequeño cambio a usuario.css para legibilidad, da pie a la proxima generacion de reportes
