//Две функции в файле - добавление и редактирование. Загружаются при загрузке странице,
//ищут переменные по id или имени, добавляются к переменным через EventListener
document.addEventListener("DOMContentLoaded", () => {
    const idInput = document.querySelector('input[name="new_id"]');
    const csInput = document.querySelector('input[name="new_callsign"]');
    const submitBtn = document.getElementById('submit-btn');
    const idError = document.getElementById('id-error');
    const csError = document.getElementById('callsign-error');
    const latinError = document.getElementById('latin-error');
    const optionalInputs = document.querySelectorAll('.add-form input[type="text"]:not([name="new_callsign"]):not([name="remarks"])');

    async function check() {
        const id = idInput.value.trim();
        const callsign = csInput.value.trim();
        idError.textContent = '';
        csError.textContent = '';
        latinError.textContent = '';
        submitBtn.disabled = false;
        //Вызываем метод из логики на валидацию
        const res = await fetch(`?ajax_check=1&id=${encodeURIComponent(id)}&callsign=${encodeURIComponent(callsign)}`);
        const json = await res.json();

        if (json.idInvalid) {
            idError.textContent = 'ID должен быть 6–7 цифр.';
        } else if (json.idExists) {
            idError.textContent = 'Такой ID уже существует.';
        }

        if (json.callsignInvalid) {
            csError.textContent = 'Позывной: 4–7 латинских символов/цифр.';
        } else if (json.callsignExists) {
            csError.textContent = 'Такой позывной уже существует.';
        }

        if (json.idInvalid || json.idExists || json.callsignInvalid || json.callsignExists) {
            submitBtn.disabled = true;
        }

        for (const input of optionalInputs) {
            if (input.value && !/^[a-zA-Z\s]*$/.test(input.value)) {
                latinError.textContent = 'Дополнительные поля должны содержать только латиницу.';
                submitBtn.disabled = true;
                break;
            }
        }
    }
    //Добавляем функцию на ввод
    idInput.addEventListener('input', check);
    csInput.addEventListener('input', check);
    optionalInputs.forEach(input => input.addEventListener('input', check));
});

//Редактирование
document.addEventListener('DOMContentLoaded', function () {
    //По клику получаем ближайшую ячеку и строку
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const td = btn.closest('td');
            const tr = td.closest('tr');
            const id = tr.cells[0].innerText;

            if (tr.classList.contains('editing')) return;

            tr.classList.add('editing');
            const cells = tr.querySelectorAll('td');
            const headers = ['id', 'callsign', 'fname', 'surname', 'city', 'state', 'country', 'remarks'];
            //Проходимся по ячейкам и  меняем то, что внутри, ограничиваем позывной и телеграм
            for (let i = 1; i < cells.length - 1; i++) {
                const field = headers[i];
                const value = cells[i].textContent.trim();
                console.log(field);
                let inputHTML = `<input type="text" name="${field}" value="${value}" style="width: 100%"`;

                if (field === 'callsign') {
                    inputHTML += ` maxlength="7" required>`;
                    cells[i].innerHTML = inputHTML;

                    const input = cells[i].querySelector('input');
                    input.addEventListener('input', () => {
                        input.value = input.value.replace(/[^A-Za-z0-9]/g, '');
                    });

                } else if (field === 'remarks') {
                    inputHTML += ` maxlength="32">`;
                    cells[i].innerHTML = inputHTML;

                    const input = cells[i].querySelector('input');
                    input.addEventListener('input', () => {
                        input.value = input.value.replace(/[^A-Za-z0-9_]/g, '');
                    });

                } else {
                    inputHTML += '>';
                    cells[i].innerHTML = inputHTML;
                }
            }
            
            //Меняем внутреннее содержимое ячейки
            td.innerHTML = `
                <button class="save-btn">💾</button>
                <button class="cancel-btn">↩️</button>
            `;
            //Отправляем в класс с логикой
            td.querySelector('.save-btn').addEventListener('click', () => {
                const data = { "id": id};
                console.log('click on test');
                for (let i = 1; i < cells.length - 1; i++) {
                    const input = cells[i].querySelector('input');
                    data[headers[i]] = input.value;
                }

                fetch('?edit=1', {
                    method: 'POST',
                    headers: {
                    'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                }).then(res => {location.reload();});
            });
            //Просто перезагружаем страницу - ничего не отправляем
            td.querySelector('.cancel-btn').addEventListener('click', () => location.reload());
        });
    });
});
