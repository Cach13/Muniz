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
