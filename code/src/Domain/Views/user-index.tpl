<h5>Список пользователей в хранилище</h5>
{% if isAdmin %}
  <a href="/user/edit/" class="btn btn-outline-primary">Добавить нового</a>
{% endif %}
      <div class="table-responsive small">
        <table class="table table-striped table-sm">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Имя</th>
              <th scope="col">Фамилия</th>
              <th scope="col">День рождения</th>
              {% if isAdmin %}
              <th score="col">Редактирование</th>
              <th scope="col">Удаление</th>
              {% endif %}
            </tr>
          </thead>
          <tbody id="tableContent">
            {% for user in users %}
            <tr id="row{{ user.getUserId() }}">       
              <td>{{ user.getUserId() }}</td>   
              <td>{{ user.getUserName() }}</td>
              <td>{{ user.getUserLastName() }}</td>
              <td>{% if user.getUserBirthday() is not empty %}
                    {{ user.getUserBirthday() | date('d-m-Y') }}
                  {% else %}
                    <b>Не задан</b>
                  {% endif %}
              </td>
              {% if isAdmin %}
                <td><a href="/user/update/?id={{ user.getUserId() }}">Редактировать</a></td>
                <td><a href="#" onclick="DeleteRow({{ user.getUserId() }})">Удалить</a></td>
              {% endif %}
            </tr>
            {% endfor %}
          </tbody>
        </table>
      </div>

<script>

    setInterval(function () {
      $.ajax({
          method: 'POST',
          url: "/user/indexRefresh/",
          data: { }
      }).done(function (response) {
          // $('.content-template').html(response);

          let users = $.parseJSON(response);
          
          if(users.length != 0){
            document.getElementById("tableContent").remove();

            let tableInit = '<tbody id="tableContent"></tbody>';

            $('.content-template table').append(tableInit);

            for(var k in users){

              let row = '<tr id="row' + users[k].id + '">';

              row += "<td>" + users[k].id + "</td>";
              row += "<td>" + users[k].username + "</td>";
              row += "<td>" + users[k].userlastname + "</td><td>";

              if(users[k].userbirthday){
                row += users[k].userbirthday;

              } else {
                row += "<b>Не задан</b>";
              }

              row += "</td>";

              {% if isAdmin %}
                row += '<td><a href="/user/update/?id=' 
                    + users[k].id 
                    + '">Редактировать</a></td>';
                
                row += '<td><a href="#" onclick="DeleteRow('
                    + users[k].id 
                    + ')">Удалить</a></td>';

              {% endif %}
              row += "</tr>";

              $('.content-template tbody').append(row);
            }
            
          }
          
      });
    }, 10000);
</script>

<script>
  function DeleteRow(userId) {
    $.ajax({
	    method: "POST", 
      url: "/user/delete/", 
	    data: { id_user: userId },
      success: function(response) {
        let result = $.parseJSON(response);

        if(result[0].consent) {
            document.getElementById("row" + userId).remove();

        } else {
          alert("Выполнить удаление невозможно или запрещено");
        }
      }, 
      error: function() {alert("Что-то пошло не так...");}
    });
  }
</script>

