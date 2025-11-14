<form action="/user/save/" method="post">
  <input id="csrf_token" type="hidden" name="csrf_token" value="{{ csrf_token }}">
  {% if need_update %}
    <input id="user_id" type="hidden" name="id" value="{{ user.getUserId }}">
  {% endif %}
  <p>
    <label for="user-name">Имя:</label>
    <input id="user-name" type="text" name="name"
    {% if need_update %} value="{{ user.getUserName() }}" {% endif %}>
  </p>
  <p>
    <label for="user-lastname">Фамилия:</label>
    <input id="user-lastname" type="text" name="lastname"
    {% if need_update %} value="{{ user.getUserLastName() }}" {% endif %}>
  </p>
  <p>
    <label for="user-login">Логин:</label>
    <input id="user-login" type="text" name="login"
    {% if need_update %} value="{{ user.getUserLogin() }}" {% endif %}>
  </p>
  <p>
    <label for="user-password">Пароль:</label>
    <input id="user-password" type="text" name="password"
    {% if need_update %} placeholder="не меняется" value = ""{% endif %}>
  </p>
  <p>
    <label for="user-birthday">День рождения:</label>
    <input id="user-birthday" type="text" name="birthday" placeholder="ДД-ММ-ГГГГ" 
    {% if need_update and user.getUserBirthday() is not empty %} 
      value="{{ user.getUserBirthday() | date('d-m-Y') }}" 
    {% else %} 
      value=""
    {% endif %}>
  </p>
  <p><input type="submit" value="Сохранить"></p>
</form>