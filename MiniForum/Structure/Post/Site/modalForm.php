<?php /** @var array $formValues */ ?>
<div id="modalWinPost">
    <div id="wrapPostForm">
        <form method="post" id="postForm<?php echo $formValues['ID'];?>" class="postForm" action="javascript: void(0)">
            <div class="input-line">
                <label for="authorPF">Ваше имя:</label> <br />
                <input value="<?php if (isset($formValues['authorPF'])) {
                    echo $formValues['authorPF'];
                } ?>" name="author" type="text"><br />
            </div>
            <div class="input-line">
                <label for="emailPF">E-mail: </label><br />
                <input name="email" value="<?php if (isset($formValues['emailPF'])) {
                    echo $formValues['emailPF'];
                } ?>" type="text"><br />
            </div class="input-line">
            <div class="input-line">
                <label for="contentPF">Сообщение: </label><br />
                <textarea id = "contentTA" name="content"><?php if (isset($formValues['content'])) {
                    echo $formValues['content'];
                } ?></textarea>
            </div>
            <div class="input-line" style="display: <?php if (isset($formValues['isPosterBlock'])) {
                echo 'block';
            } else {
                echo 'none';
            } ?>" >
            <?php
            if (isset($_REQUEST['isAuthorized']) && $_REQUEST['isAuthorized'] == "true") {
                echo '<label for="authorPF">Ответ от постера: </label><input type="checkbox" name="is_poster" value="true">';
            }
?>
            </div>
            <div class="post-btn input-line">
                <button name="sendPost" type="submit" onclick='<?php echo $formValues['buttonMethod'] ?>(<?php
                                                                                        if ($formValues['buttonMethod'] == 'ajaxAddNewPost') {
                                                                                            echo 'false,';
                                                                                            echo '"' . $formValues['pageStructure'] . '"' . ",";
                                                                                        }
if ($formValues['buttonMethod'] == 'ajaxAddNewPost') {
    echo $formValues['mainParentId'] . "," ;
}
echo $formValues['ID'];
?>);'>Отправить</button>
                &nbsp;&nbsp;&nbsp;&nbsp;<button type="submit" onclick="loadForm.closeModalForm(); return false;">Закрыть</button>
            </div>
            <input type="hidden" name="ID" value="<?php if (isset($formValues['ID'])) {
                echo $formValues['ID'];
            } else {
                echo '0';
            } ?>">
            <input type="hidden" name="parent_id" value="<?php if (isset($formValues['parentPostID'])) {
                echo $formValues['ID'];
            } else {
                echo '0';
            } ?>">
            <input type="hidden" name="main_parent_id" value="<?php if (isset($formValues['mainParentId'])) {
                echo $formValues['mainParentId'];
            } else {
                echo '0';
            } ?>">
            <input type="hidden" name="page_structure" value="<?php if (isset($formValues['pageStructure'])) {
                echo $formValues['pageStructure'];
            } else {
                echo '0';
            } ?>">
        </form>
    </div>
</div>
