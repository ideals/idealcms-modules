/**
 * Модальное окно
 */
var modalWindow = {
    _block: null,
    _win: null,
    isShow: null,
    //Метод инициализации блокирующего фона
    initBlock: function () {
        _block = document.getElementById('blockscreen'); //Получаем наш блокирующий фон по ID

        //Если он не определен, то создадим его
        if (!_block) {
            var parent = document.getElementsByTagName('body')[0]; //Получим первый элемент тега body
            var obj = parent.firstChild; //Для того, чтобы вставить наш блокирующий фон в самое начало тега body
            _block = document.createElement('div'); //Создаем элемент div
            _block.id = 'blockscreen'; //Присваиваем ему наш ID
            parent.insertBefore(_block, obj); //Вставляем в начало
            _block.onclick = function () {
                modalWindow.close();
            } //Добавим обработчик события по нажатию на блокирующий экран - закрыть модальное окно.
        }
        _block.style.display = 'inline'; //Установим CSS-свойство
    },
    //Метод инициализации диалогового окна
    initWin: function (width, html) {
        _win = document.getElementById('modalwindow'); //Получаем наше диалоговое окно по ID
        //Если оно не определено, то также создадим его по аналогии
        if (!_win) {
            var parent = document.getElementsByTagName('body')[0];
            var obj = parent.firstChild;
            _win = document.createElement('div');
            _win.id = 'modalwindow';
            _win.style.padding = '0 0 0 0';
            parent.insertBefore(_win, obj);
        }
        _win.style.width = width + 'px'; //Установим ширину окна
        _win.style.display = 'inline'; //Зададим CSS-свойство

        _win.innerHTML = html; //Добавим нужный HTML-текст в наше диалоговое окно

        //Установим позицию по центру экрана

        _win.style.left = '50%'; //Позиция по горизонтали
        _win.style.top = '50%'; //Позиция по вертикали

        //Выравнивание по центру путем задания отрицательных отступов
        _win.style.marginTop = -(_win.offsetHeight / 2) + 'px';
        _win.style.marginLeft = -(width / 2) + 'px';
    },
    //Метод закрытия модального окна
    close: function () {
        document.getElementById('blockscreen').style.display = 'none';
        //document.getElementById('modalwindow').style.display = 'none';
        modalWindow.isShow = false;
    },
    //Метод появления модльного окна
    show: function (width, html) {
        modalWindow.initBlock();
        modalWindow.initWin(width, html);
        modalWindow.isShow = true;
    }
}

/**
 * Объект включающий загрузку формы, открытие её в модальном окне и в раздвигающемся блоке
 */
var loadForm = new Object();
loadForm.isAuthorized = false;
loadForm.form = '';


/**
 * Метод загрузки формы, встроенной в блок с постом
 * @param formValues
 * @param formLoad
 */
loadForm.ajaxLoadForm = function (formValues, formLoad) {
    $.ajax({
        url: "/?mode=ajax&controller=MiniForum\\Structure\\Post\\Site&action=" + formLoad,
        async: false,
        data: {isAuthorized: loadForm.isAuthorized, formValues: jQuery.param(formValues)},
        success: function (form) {
            loadForm.form = form;
        }
    })
}

/**
 * Открытие мадольного окна
 * @param formValues
 */
loadForm.openModalForm = function (formValues) {
    loadForm.ajaxLoadForm(formValues, 'getModalForm');
    modalWindow.show(360, loadForm.form);
}

/**
 * Закрытие модального окна
 */
loadForm.closeModalForm = function () {
    if ($('#modalwindow').length) {
        $('#modalwindow').remove();
    }
    modalWindow.close();
}

/**
 * Открытие формы ответа
 * @param formValues
 * @returns {boolean}
 */
loadForm.openAnswerForm = function (formValues) {
    if ($('#form-add-post-' + formValues.ID).css('display') == 'block') {
        loadForm.closeAnswerForm(formValues.ID);
        return false;
    }

    //загружаем форму ответа
    loadForm.ajaxLoadForm(formValues, 'getAnswerForm');
    //вставляем форму в блок под родительским постом
    $('#form-add-post-' + formValues.ID).append(loadForm.form);
    //раскрываем форму
    $('#form-add-post-' + formValues.ID).show();
}

/**
 * Закрытие формы ответа
 * @param ID
 */
loadForm.closeAnswerForm = function (ID) {
    //скрываем форму
    $('#form-add-post-' + ID).hide();
    //удаляем форму
    var form = $('#form-add-post-' + ID).children('.postAnswer');
    if ($(form).length) {
        $(form).remove();
    }
}

/**
 * Генерация данных для создания сообщения на форуме
 * @param ID
 * @param mainParentId
 * @param pageStructure
 */
function addPost(ID, mainParentId, pageStructure) {
    var formValues = {
        ID: ID,
        mainParentId: mainParentId,
        pageStructure: pageStructure,
        buttonMethod: 'ajaxAddNewPost',
        isPosterBlock: true
    }
    loadForm.openModalForm(formValues);
}

/**
 * Генерация данных для ответа
 * @param ID
 * @param mainParentId
 * @param pageStructure
 */
function addPostAnswer(ID, mainParentId, pageStructure) {
    var formValues = {
        ID: ID,
        mainParentId: mainParentId,
        pageStructure: pageStructure,
        buttonMethod: 'ajaxAddNewPost',
        ajaxAddNewPost: true,
        isPosterBlock: true
    }
    loadForm.openAnswerForm(formValues);
}

/**
 * Генерация данных для редактирования сообщения
 * @param ID
 * @param email
 */
function updatePost(ID, email) {
    var postLine = '#post-line-' + ID;
    var content = jQuery.trim($(postLine).children('.post-message').html());
    var formValues = {
        ID: ID,
        mainParentId: 0,
        pageStructure: 0,
        postID: ID,
        buttonMethod: 'ajaxUpdatePost'
    }

    //загружаем и открываем модальное окно
    loadForm.openModalForm(formValues);
}

/**
 * Добавление сообщения на форум
 * @param goToPost
 * @param pageStructure TODO выпилить
 * @param mainParentId
 * @param ID
 */
function ajaxAddNewPost(goToPost, pageStructure, mainParentId, ID) {
    var idElement = '#postForm' + ID;
    var form = $(idElement).serialize();
    $(idElement).show();
    $.post(
        "/?mode=ajax&controller=MiniForum\\Structure\\Post\\Site&action=inset",
        {
            form: form
        },
        function newPostMessage(msg) {
            var m = parseInt(msg);
            if (!isNaN(m)) {
                if (loadForm.isAuthorized) {
                    msg = 'Ответ успешно добавлен и опубликован.';
                } else {
                    msg = 'Ответ успешно добавлен. После просмотра модератором, он будет доступен для просмотра другим пользователям';
                }
            }
            //Если ответ добавлен с форума
            if (!goToPost) {
                message(msg);
                return;
            }
            //Если ответ добавлен из раздела
            if (modalWindow.isShow === true) modalWindow.close();
            message(msg);
            if (!isNaN(m)) {
                window.location.reload();
                window.location.href = '/forum/' + mainParentId + '.html';
            }
        }

    )
}

/**
 * Удаление сообщения с форума
 * @param ID
 * @param mainParentId
 * @param parentId
 */
function ajaxDeletePost(ID, mainParentId, parentId) {
    $.post(
        "/?mode=ajax&controller=MiniForum\\Structure\\Post\\Site&action=delete",
        {
            ID: ID,
            parent_id: parentId,
            main_parent_id: mainParentId
        },
        function delMsg(msg) {
            if (mainParentId == '0') {
                alert(msg);
                window.location.href = document.referrer;
            } else {
                message(msg);
            }
        }
    )
}

/**
 * Редактирование сообщения
 * @param ID
 */
function ajaxUpdatePost(ID) {
    var idElement = '#postForm' + ID;
    var form = $(idElement).serialize();
    $(idElement).show();
    $.post(
        "/?mode=ajax&controller=MiniForum\\Structure\\Post\\Site&action=update",
        {
            form: form
        },
        message
    )
}

/**
 * Проверка модератором сообщения
 * @param ID
 * @param isModerated
 */
function ajaxModeratedPost(ID, isModerated) {
    $.post(
        "/?mode=ajax&controller=MiniForum\\Structure\\Post\\Site&action=moderate",
        {
            ID: ID,
            isModerated: isModerated
        },
        function modMsg(msg) {
            message(msg);
            window.location.reload();
        }
    )
}

function message(msg) {
    // Если сообщение пустое
    if (msg == '') {
        return;
    }
    if (msg.substr(0, 14) == 'MSG_Validation') {
        msg = msg.substr(15, msg.length);
        alert(msg);
    } else {
        if (modalWindow.isShow === true) modalWindow.close();
        alert(msg);
        window.location.reload();
    }
    if (modalWindow.isShow !== null) $('#postForm').show();
}
