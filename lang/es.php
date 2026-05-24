<?php
return [
    /*
    |--------------------------------------------------------------------------
    | LANDING / GENERAL
    |--------------------------------------------------------------------------
    */
    "app_title" => "Serious Game: Colesterol",
    "landing_description" => "Aprende sobre el colesterol alto mediante un juego interactivo con preguntas, puntaje, retroalimentación y salas de competencia.",
    "play_solo" => "Jugar solo",
    "join_room" => "Unirse a sala",
    "create_room" => "Crear sala",
    "back" => "Volver",
    "game" => "Juego",
    "loading" => "Cargando...",
    "error" => "Error",
    "spanish" => "Español",
    "english" => "Inglés",
    "language" => "Idioma",
    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */
    "register_title" => "Registro de usuario",
    "register_description" => "Regístrate para comenzar el juego educativo sobre colesterol.",
    "login_title" => "Iniciar sesión",
    "name" => "Nombre",
    "email" => "Correo electrónico",
    "password" => "Contraseña",
    "register_button" => "Registrarse",
    "login_button" => "Ingresar",
    "already_account" => "¿Ya tienes cuenta?",
    "no_account" => "¿No tienes cuenta?",
    "login_link" => "Inicia sesión",
    "register_link" => "Regístrate",
    "logout" => "Cerrar sesión",
    /*
    |--------------------------------------------------------------------------
    | GAME
    |--------------------------------------------------------------------------
    */
    "score" => "Puntaje",
    "lives" => "Vidas",
    "difficulty" => "Dificultad",
    "difficulty_level" => "Nivel de dificultad",
    "loading_questions" => "Cargando preguntas...",
    "question" => "Pregunta",
    "correct_answer" => "Respuesta correcta",
    "correct_answers" => "Respuestas correctas",
    "correct" => "Correcto",
    "incorrect" => "Incorrecto",
    "game_over" => "Juego terminado",
    "game_completed" => "Juego completado",
    "game_finished" => "Juego finalizado",
    "final_score" => "Puntaje final",
    "remaining_lives" => "Vidas restantes",
    "saving_result" => "Guardando resultado...",
    "result_saved" => "Resultado guardado correctamente",
    "result_not_saved" => "No se pudo guardar el resultado",
    "time" => "Tiempo",
    "time_limit" => "Tiempo por pregunta (segundos)",
    "time_out" => "Tiempo terminado",
    /*
    |--------------------------------------------------------------------------
    | ADAPTIVE DIFFICULTY
    |--------------------------------------------------------------------------
    */
    "adaptive_room_description" => "La sala inicia con dificultad básica y el sistema ajusta automáticamente el nivel según el rendimiento y el tiempo de respuesta de los jugadores.",
    "adaptive_difficulty" => "Dificultad adaptativa",
    "new_difficulty" => "Nueva dificultad",
    "final_difficulty" => "Dificultad final",
    /*
    |--------------------------------------------------------------------------
    | SOLO GAME PAGES
    |--------------------------------------------------------------------------
    */
    "history" => "Ver historial",
    "ranking" => "Ver ranking",
    "dashboard" => "Ver dashboard",
    "ranking_description" => "Visualiza los mejores puntajes registrados en el sistema.",
    "dashboard_description" => "Consulta métricas y desempeño de los usuarios.",
    /*
    |--------------------------------------------------------------------------
    | DIFFICULTY
    |--------------------------------------------------------------------------
    */
    "select_difficulty" => "Selecciona la dificultad",
    "difficulty_description" => "Elige un nivel antes de comenzar la partida.",
    "easy" => "Fácil",
    "medium" => "Medio",
    "hard" => "Difícil",
    /*
    |--------------------------------------------------------------------------
    | QUESTIONS / ADMIN QUESTIONS
    |--------------------------------------------------------------------------
    */
    "admin_title" => "Administrador de preguntas",
    "admin_description" => "Gestiona preguntas manuales, generadas automáticamente e importadas desde CSV.",
    "admin_questions" => "Administrar preguntas",
    "admin_questions_description" => "Crea, edita, importa y genera preguntas mediante IA.",
    "create_question" => "Crear pregunta manualmente",
    "save_question" => "Guardar pregunta",
    "update_question" => "Actualizar pregunta",
    "edit" => "Editar",
    "delete" => "Eliminar",
    "question_saved" => "Pregunta guardada correctamente",
    "question_updated" => "Pregunta actualizada correctamente",
    "question_deleted" => "Pregunta eliminada correctamente",
    "confirm_delete_question" => "¿Eliminar esta pregunta?",
    "category" => "Categoría",
    "category_placeholder" => "Ej. Tipos de colesterol",
    "option_a" => "Opción A",
    "option_b" => "Opción B",
    "option_c" => "Opción C",
    "option_d" => "Opción D",
    "correct_option" => "Respuesta correcta",
    "explanation" => "Explicación",
    "cancel_edit" => "Cancelar edición",
    /*
    |--------------------------------------------------------------------------
    | AI GENERATOR
    |--------------------------------------------------------------------------
    */
    "generator" => "Generador automático",
    "generator_description" => "Genera un borrador de pregunta usando inteligencia artificial. Revísala y edítala antes de guardarla.",
    "generated_ready" => "Pregunta generada. Revísala antes de guardar.",
    "topic" => "Tema",
    "topic_placeholder" => "Ej. LDL y colesterol malo",
    "generate_question" => "Generar pregunta",
    /*
    |--------------------------------------------------------------------------
    | MASS GENERATOR
    |--------------------------------------------------------------------------
    */
    "mass_generator" => "Generador masivo con IA",
    "mass_generator_description" => "Genera varias preguntas automáticamente y las inserta directamente en la base de datos.",
    "quantity" => "Cantidad",
    "generate_and_insert" => "Generar e insertar",
    "mass_generated_success" => "Preguntas generadas e insertadas correctamente",
    /*
    |--------------------------------------------------------------------------
    | CSV IMPORT
    |--------------------------------------------------------------------------
    */
    "import_csv" => "Importar CSV",
    "csv_description" => "Columnas CSV: question, option_a, option_b, option_c, option_d, correct_option, explanation, category, difficulty_level, language",
    "csv_file" => "Archivo CSV",
    /*
    |--------------------------------------------------------------------------
    | QUESTION STATUS
    |--------------------------------------------------------------------------
    */
    "status" => "Estado",
    "origin" => "Origen",
    "manual" => "Manual",
    "ai" => "IA",
    "active" => "Activa",
    "inactive" => "Inactiva",
    "active_status" => "Estado activo",
    "verified" => "Verificada",
    "pending" => "Pendiente",
    "rejected" => "Rechazada",
    "verified_questions" => "Preguntas verificadas",
    "pending_questions" => "Preguntas pendientes",
    "average_difficulty" => "Dificultad promedio",
    /*
    |--------------------------------------------------------------------------
    | ROOMS
    |--------------------------------------------------------------------------
    */
    "rooms_title" => "Salas de juego",
    "room_name" => "Nombre de la sala",
    "room_code" => "Código de sala",
    "room_lobby" => "Lobby de sala",
    "player_name" => "Nombre del jugador",
    "room_share_link" => "Link para compartir la sala",
    "copy_room_link" => "Copiar link de sala",
    "link_copied" => "Link copiado",
    "connected_players" => "Jugadores conectados",
    "start_game" => "Iniciar partida",
    "no_players_yet" => "Aún no hay jugadores",
    "game_started" => "Partida iniciada",
    "room_ranking" => "Ranking de sala",
    "live_ranking" => "Ranking en vivo",
    "podium" => "Podio final",
    "full_ranking" => "Ranking completo",
    "precision" => "Precisión",
    "waiting_room" => "Esperando que el docente inicie la sala...",
    "room_paused" => "La sala está pausada por el docente.",
    /*
    |--------------------------------------------------------------------------
    | ROOM CONTROLS
    |--------------------------------------------------------------------------
    */
    "room_control" => "Control de sala",
    "pause_room" => "Pausar",
    "resume_room" => "Reanudar",
    "next_question" => "Siguiente pregunta",
    "finish_room" => "Finalizar sala",
    "current_question" => "Pregunta actual",
    "time_left" => "Tiempo restante",
    /*
    |--------------------------------------------------------------------------
    | ROOM CREATION
    |--------------------------------------------------------------------------
    */
    "create_room_description" => "Crea una sala de juego para que los estudiantes participen con un código.",
    "question_count" => "Cantidad de preguntas",
    "question_mode" => "Modo de preguntas",
    "random_questions" => "Preguntas aleatorias",
    "selected_questions" => "Preguntas seleccionadas",
    "select_questions" => "Seleccionar preguntas",
    "select_questions_description" => "Carga y selecciona las preguntas que deseas incluir en esta sala.",
    "load_questions" => "Cargar preguntas",
    "select_at_least_one_question" => "Selecciona al menos una pregunta",
    /*
    |--------------------------------------------------------------------------
    | ADMIN DASHBOARD
    |--------------------------------------------------------------------------
    */
    "admin_login" => "Soy administrador",
    "admin_dashboard" => "Panel de administración",
    "admin_dashboard_description" => "Gestiona el contenido educativo, las salas de juego y los resultados del sistema.",
    "back_to_admin" => "Volver al panel",
    "back_to_game" => "Volver al juego",
    "public_view" => "Vista pública",
    "public_view_description" => "Volver a la pantalla principal del sistema.",
    /*
    |--------------------------------------------------------------------------
    | PLAYER DASHBOARD
    |--------------------------------------------------------------------------
    */
    "player_dashboard" => "Panel del jugador",
    "start_solo_game" => "Iniciar partida",
    "start_solo_game_description" => "Juega una partida individual con dificultad adaptativa.",
    "join_room_description" => "Ingresa a una sala usando un código compartido por el docente.",
    "history_description" => "Consulta tus partidas anteriores y resultados.",
    "player_dashboard" => "Panel del jugador",
    "player_options" => "Opciones del jugador",
    "best_score" => "Mejor puntaje",
    "start_solo_game" => "Iniciar partida",
    "start_solo_game_description" => "Juega una partida individual con dificultad adaptativa.",
    "join_room_description" => "Ingresa a una sala usando un código compartido por el docente.",
    "history_description" => "Consulta tus partidas anteriores y resultados.",
    /*
    |--------------------------------------------------------------------------
    | ANALYTICS
    |--------------------------------------------------------------------------
    */
    "educational_analytics" => "Analítica educativa",
    "system_summary" => "Resumen del sistema",
    "overall_accuracy" => "Precisión general",
    "average_score" => "Promedio de puntaje",
    "total_users" => "Usuarios registrados",
    "total_questions" => "Preguntas registradas",
    "total_games" => "Partidas registradas",
    "total_rooms" => "Salas creadas",
    "active_rooms" => "Salas en espera",
    "total_correct_answers" => "Respuestas correctas",
    "total_answered_questions" => "Preguntas respondidas",
    "performance_by_difficulty" => "Rendimiento por dificultad",
    "top_players" => "Mejores jugadores",
    /*
    |--------------------------------------------------------------------------
    | EXTRA
    |--------------------------------------------------------------------------
    */
    "registered_questions" => "Preguntas registradas"
];