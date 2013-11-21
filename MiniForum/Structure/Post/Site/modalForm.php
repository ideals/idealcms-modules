<div id="modalWinPost">
    <div id="wrapPostForm">
        <form method="post" id="postForm<?php echo $formValues['ID'];?>" class="postForm" action="javascript: void(0)">
            <div class="input-line">
                <label for="authorPF">Ваше имя:</label> <br />
                <input value="<?php if (isSet($formValues['authorPF'])) echo $formValues['authorPF']; ?>" name="author" type="text"><br />
            </div>
            <div class="input-line">
                <label for="emailPF">E-mail: </label><br />
                <input name="email" value="<?php if (isSet($formValues['emailPF'])) echo $formValues['emailPF']; ?>" type="text"><br />
            </div class="input-line">
            <div class="input-line">
                <label for="contentPF">Сообщение: </label><br />
                <textarea id = "contentTA" name="content"><?php if (isSet($formValues['content'])) echo $formValues['content']; ?></textarea>
            </div>
            <div class="input-line" style="display: <?php if (isSet($formValues['isPosterBlock'])) echo 'block'; else echo 'none'; ?>" >
            <?php
            if (isset($_REQUEST['isAuthorized']) && $_REQUEST['isAuthorized'] == "true") {
                echo '<label for="authorPF">Ответ от постера: </label><input type="checkbox" name="is_poster" value="true">';
            }
            ?>
            </div>
            <div class="post-btn input-line">
                <button name="sendPost" type="submit" onclick='<?php echo $formValues['buttonMethod'] ?>(<?php
                                                                                                    if ($formValues['buttonMethod'] == 'ajaxAddNewPost') echo 'false,';
                                                                                                    if ($formValues['buttonMethod'] == 'ajaxAddNewPost') echo '"' . $formValues['pageStructure']. '"'  . ",";
                                                                                                    if ($formValues['buttonMethod'] == 'ajaxAddNewPost') echo $formValues['mainParentId'] . "," ;
                                                                                                    echo $formValues['ID'];
                                                                                                ?>);'>Отправить</button>
                &nbsp;&nbsp;&nbsp;&nbsp;<button type="submit" onclick="loadForm.closeModalForm(); return false;">Закрыть</button>
            </div>
            <input type="hidden" name="ID" value="<?php if (isSet($formValues['ID'])) echo $formValues['ID']; else echo '0'; ?>">
            <input type="hidden" name="parent_id" value="<?php if (isSet($formValues['parentPostID'])) echo $formValues['ID']; else echo '0'; ?>">
            <input type="hidden" name="main_parent_id" value="<?php if (isSet($formValues['mainParentId'])) echo $formValues['mainParentId']; else echo '0'; ?>">
            <input type="hidden" name="page_structure" value="<?php if (isSet($formValues['pageStructure'])) echo $formValues['pageStructure']; else echo '0'; ?>">
        </form>
    </div>
</div>