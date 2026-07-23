<?php

return [
    'title' => 'Пользователи',
    'add' => 'Добавить пользователя',
    'edit' => 'Редактировать пользователя',
    'empty' => 'Пользователей нет',
    'actions' => 'Действия',
    'delete_confirm' => 'Вы уверены, что хотите удалить этого пользователя?',
    'cant_delete_self' => 'Нельзя удалить собственный аккаунт',

    'roles' => [
        'administrator' => 'Администратор',
        'manager' => 'Менеджер',
        'operator' => 'Оператор',
        'all' => 'Все роли',
    ],

    'fields' => [
        'name' => 'Имя',
        'email' => 'Email',
        'password' => 'Пароль',
        'password_confirmation' => 'Подтверждение пароля',
        'role' => 'Роль',
        'created_at' => 'Дата регистрации',
    ],

    'placeholders' => [
        'name' => 'Например, Иван Иванов',
        'email' => 'name@example.com',
        'password' => 'Минимум 8 символов',
        'password_confirmation' => 'Повторите пароль',
    ],

    'filters' => [
        'all_roles' => 'Все роли',
        'reset' => 'Сбросить',
    ],

    'password_hint' => 'Оставьте пустым, чтобы не менять',
];
