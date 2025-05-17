document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const formPrograma = document.getElementById('form-programa');
    const selectMateria = document.getElementById('materia');
    const selectUnidad = document.getElementById('unidad');
    const inputTema = document.getElementById('tema');
    const inputHoras = document.getElementById('horas');
    const btnAgregarTema = document.getElementById('agregar-tema');
    const listaTemas = document.getElementById('lista-temas');
    const btnAgregarHorario = document.getElementById('agregar-horario');
    const mensajeRespuesta = document.getElementById('mensaje-respuesta');

    // Array para almacenar temas añadidos temporalmente
    let temasAgregados = [];

    // Cargar materias al iniciar
    cargarMaterias();

    // Event Listeners
    selectMateria.addEventListener('change', cargarUnidades);
    btnAgregarTema.addEventListener('click', agregarTema);
    btnAgregarHorario.addEventListener('click', agregarHorario);
    formPrograma.addEventListener('submit', enviarFormulario);

    // Configurar eliminación para el primer horario
    configurarBotonesBorrar();

    // Funciones
    function cargarMaterias() {
        fetch('get_materias.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    mostrarMensaje(data.error, 'error');
                    return;
                }
                
                selectMateria.innerHTML = '<option value="">Seleccione una materia</option>';
                data.forEach(materia => {
                    const option = document.createElement('option');
                    option.value = materia.id_programa;
                    option.textContent = materia.nombre_materia;
                    selectMateria.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error al cargar materias:', error);
                mostrarMensaje('Error al cargar las materias', 'error');
            });
    }

    function cargarUnidades() {
        const materiaId = selectMateria.value;
        
        if (!materiaId) {
            selectUnidad.innerHTML = '<option value="">Primero seleccione una materia</option>';
            selectUnidad.disabled = true;
            return;
        }

        fetch(`get_unidades.php?id_programa=${materiaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    mostrarMensaje(data.error, 'error');
                    return;
                }
                
                selectUnidad.innerHTML = '<option value="">Seleccione una unidad</option>';
                data.forEach(unidad => {
                    const option = document.createElement('option');
                    option.value = unidad.id_unidad;
                    option.textContent = `Unidad ${unidad.numero_unidad}: ${unidad.nombre_unidad}`;
                    selectUnidad.appendChild(option);
                });
                selectUnidad.disabled = false;
            })
            .catch(error => {
                console.error('Error al cargar unidades:', error);
                mostrarMensaje('Error al cargar las unidades', 'error');
            });
    }

    function agregarTema() {
        const nombreTema = inputTema.value.trim();
        const horas = parseInt(inputHoras.value);
        const unidadId = selectUnidad.value;
        const unidadText = selectUnidad.options[selectUnidad.selectedIndex].text;
        
        // Validaciones
        if (!unidadId) {
            mostrarMensaje('Debe seleccionar una unidad', 'error');
            return;
        }
        
        if (!nombreTema) {
            mostrarMensaje('Ingrese el nombre del tema', 'error');
            return;
        }
        
        if (isNaN(horas) || horas < 1) {
            mostrarMensaje('Las horas deben ser un número positivo', 'error');
            return;
        }
        
        // Crear objeto tema
        const tema = {
            unidadId: unidadId,
            unidadNombre: unidadText,
            nombreTema: nombreTema,
            horasEstimadas: horas
        };
        
        // Agregar tema a la lista
        temasAgregados.push(tema);
        actualizarListaTemas();
        
        // Limpiar campos
        inputTema.value = '';
        inputHoras.value = '';
        
        mostrarMensaje('Tema agregado correctamente', 'exito');
    }

    function actualizarListaTemas() {
        // Vaciar la lista actual
        listaTemas.innerHTML = '';
        
        // Si no hay temas, mostrar mensaje
        if (temasAgregados.length === 0) {
            const li = document.createElement('li');
            li.textContent = 'No hay temas agregados';
            li.className = 'no-temas';
            listaTemas.appendChild(li);
            return;
        }
        
        // Agregar cada tema a la lista
        temasAgregados.forEach((tema, index) => {
            const li = document.createElement('li');
            li.innerHTML = `
                <strong>${tema.unidadNombre}</strong> - 
                ${tema.nombreTema} (${tema.horasEstimadas} horas)
                <input type="hidden" name="tema_unidad_id[]" value="${tema.unidadId}">
                <input type="hidden" name="tema_nombre[]" value="${tema.nombreTema}">
                <input type="hidden" name="tema_horas[]" value="${tema.horasEstimadas}">
                <button type="button" class="btn-eliminar-tema" data-index="${index}">✕</button>
            `;
            listaTemas.appendChild(li);
        });
        
        // Configurar botones de eliminar
        document.querySelectorAll('.btn-eliminar-tema').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                eliminarTema(index);
            });
        });
    }

    function eliminarTema(index) {
        if (index >= 0 && index < temasAgregados.length) {
            temasAgregados.splice(index, 1);
            actualizarListaTemas();
            mostrarMensaje('Tema eliminado', 'exito');
        }
    }

    function agregarHorario() {
        const contenedorHorarios = document.querySelector('.horario-container');
        const nuevoHorario = document.createElement('div');
        nuevoHorario.className = 'form-group horario-row';
        nuevoHorario.innerHTML = `
            <select name="dia[]" required>
                <option value="">Seleccione día</option>
                <option value="Lunes">Lunes</option>
                <option value="Martes">Martes</option>
                <option value="Miércoles">Miércoles</option>
                <option value="Jueves">Jueves</option>
                <option value="Viernes">Viernes</option>
                <option value="Sábado">Sábado</option>
            </select>
            <input type="time" name="hora_inicio[]" required>
            <input type="time" name="hora_fin[]" required>
            <button type="button" class="btn-eliminar">✕</button>
        `;
        contenedorHorarios.appendChild(nuevoHorario);
        
        // Configurar botón eliminar del nuevo horario
        const btnEliminar = nuevoHorario.querySelector('.btn-eliminar');
        btnEliminar.addEventListener('click', function() {
            contenedorHorarios.removeChild(nuevoHorario);
        });
    }

    function configurarBotonesBorrar() {
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', function() {
                const filaHorario = this.parentElement;
                const contenedorHorarios = document.querySelector('.horario-container');
                
                // No eliminar si es el único horario
                if (document.querySelectorAll('.horario-row').length > 1) {
                    contenedorHorarios.removeChild(filaHorario);
                } else {
                    mostrarMensaje('Debe haber al menos un horario', 'error');
                }
            });
        });
    }

    function enviarFormulario(e) {
        e.preventDefault();
        
        // Validar que se hayan agregado temas
        if (temasAgregados.length === 0) {
            mostrarMensaje('Debe agregar al menos un tema', 'error');
            return;
        }
        
        // Validar fecha de evaluación
        const fechaEvaluacion = document.getElementById('fecha-evaluacion').value;
        if (!fechaEvaluacion) {
            mostrarMensaje('Debe seleccionar una fecha de evaluación', 'error');
            return;
        }
        
        // Crear FormData para enviar
        const formData = new FormData(formPrograma);
        
        // Enviar datos mediante fetch
        fetch('procesar_programa.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                mostrarMensaje(data.error, 'error');
            } else {
                mostrarMensaje(data.mensaje, 'exito');
                // Resetear formulario y temas
                formPrograma.reset();
                temasAgregados = [];
                actualizarListaTemas();
                selectUnidad.innerHTML = '<option value="">Primero seleccione una materia</option>';
                selectUnidad.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error al enviar el formulario:', error);
            mostrarMensaje('Error al procesar la solicitud', 'error');
        });
    }

    function mostrarMensaje(mensaje, tipo) {
        mensajeRespuesta.textContent = mensaje;
        mensajeRespuesta.className = `mensaje ${tipo}`;
        mensajeRespuesta.style.display = 'block';
        
        // Ocultar después de 5 segundos
        setTimeout(() => {
            mensajeRespuesta.style.display = 'none';
        }, 5000);
    }

    // Inicializar la lista de temas
    actualizarListaTemas();
});