<?php
if ($_POST['update'] == 'update') {
    update_option('display_finndy_text', $_POST['display_finndy_text']);

    echo '<div id="message" class="updated fade"><p>更新成功！</p></div>';
}
?>
<div>  
        <h2>发源地发布接口</h2>  
        <form method="post" action="admin.php?page=finndywp/finndy_html.php">  
          
 
            <p>  <span> token</span>
                <input type="text"
                    name="display_finndy_text" 
                    id="display_finndy_text" 
                   value="<?php echo get_option('display_finndy_text'); ?>"/>  
            </p>  
 
            <p>  
                <input type="hidden" name="update" value="update" />  
                <input type="submit" value="保存" class="button-primary" />  
            </p>  
        </form>  
    </div>

