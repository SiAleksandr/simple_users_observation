{% if not user_authorized %}
    <div class="col-md-3 text-end">
        <a href="/user/auth/" class="btn btn-primary">Войти</a>
    </div>
{% else %}
    <p class="col-md-3 text-end">Добро пожаловать на сайт {{ current_user }}!</p>
    <div class="col-md-3 text-end">
        <a href="/user/logout/" class="btn btn-primary">Выход</a>
    </div>
{% endif %}
