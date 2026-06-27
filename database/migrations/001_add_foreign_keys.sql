-- Ejecutar solo despues de validar que no existan registros huerfanos.

ALTER TABLE `game_rooms`
  ADD CONSTRAINT `fk_game_rooms_created_by`
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
  ON DELETE SET NULL;

ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE CASCADE;

ALTER TABLE `room_players`
  ADD CONSTRAINT `fk_room_players_room`
  FOREIGN KEY (`room_id`) REFERENCES `game_rooms` (`id`)
  ON DELETE CASCADE;

ALTER TABLE `room_questions`
  ADD CONSTRAINT `fk_room_questions_room`
  FOREIGN KEY (`room_id`) REFERENCES `game_rooms` (`id`)
  ON DELETE CASCADE,
  ADD CONSTRAINT `fk_room_questions_question`
  FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`)
  ON DELETE CASCADE;

ALTER TABLE `room_question_requirements`
  ADD CONSTRAINT `fk_room_question_requirements_room`
  FOREIGN KEY (`room_id`) REFERENCES `game_rooms` (`id`)
  ON DELETE CASCADE;

ALTER TABLE `game_answers`
  ADD CONSTRAINT `fk_game_answers_user`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE SET NULL,
  ADD CONSTRAINT `fk_game_answers_room`
  FOREIGN KEY (`room_id`) REFERENCES `game_rooms` (`id`)
  ON DELETE CASCADE,
  ADD CONSTRAINT `fk_game_answers_question`
  FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`)
  ON DELETE CASCADE;

ALTER TABLE `game_results`
  ADD CONSTRAINT `fk_game_results_user`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE SET NULL,
  ADD CONSTRAINT `fk_game_results_room`
  FOREIGN KEY (`room_id`) REFERENCES `game_rooms` (`id`)
  ON DELETE CASCADE;

ALTER TABLE `user_badges`
  ADD CONSTRAINT `fk_user_badges_user`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE CASCADE;
