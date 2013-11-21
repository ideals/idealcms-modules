<div id="modalWinPost">
    <div id="wrapPostForm">
        <form method="post" id="postForm" action="javascript: void(0)">
            <div class="input-line">
                <label for="authorPF">Ваше имя(псевдоним):</label> <br /> <input id="authorPF" value="" name="author" type="text"><br />
            </div>
            <div class="input-line">
                <label for="emailPF">E-mail: </label><br /> <input id="emailPF" name="email" value="" type="text"><br />
            </div class="input-line">
            <div class="input-line">
                <label for="contentPF">Сообщение: </label><br /> <textarea id="contentPF" name="content"></textarea>
            </div>
            <div class="input-line" style="display: none" id="isPosterBlock">
            <?php
            if (isset($_REQUEST['isAuthorized']) && $_REQUEST['isAuthorized'] == "true") {
                echo '<label for="authorPF">Ответ от постера: </label><input type="checkbox" id="isPoster" name="is_poster" value="true">';
            }
            ?>
            </div>
            <div class="post-btn input-line">
                <button name="sendPost" id="sendPost" type="submit">Отправить</button>
                <button type="submit" onclick="modalWindow.close();">Закрыть</button>
            </div>
            <input type="hidden" id="postID" name="ID" value="0">
            <input type="hidden" id="parentPostID" name="parent_id" value="0">
            <input type="hidden" id="mainParentPostID" name="main_parent_id" value="0">
            <input type="hidden" id="pageStructurePostId" name="page_structure" value="0">
        </form>
    </div>
</div>