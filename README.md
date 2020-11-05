# tsTemplate
Шаблонизатор...

Пример, закомментирован...

```

 public function display($tpl_file){

    global $_LANG;
    extract($this->tpl_vars);
    
    // php 7.3   
    //$dir = TEMPLATE_DIR . $this->tpl_folder.'/'.$this->tpl_file;
    //$objfile = PATH. '/cache/templates/com_comments_list_all.tpl.php';
    //include 'tsTemplate.php';
    //$T = new tsTemplate();
    //$T -> complie($dir, $objfile);
    //include($objfile);

    include(TEMPLATE_DIR . $this->tpl_folder.'/'.$this->tpl_file);

 }
```    

Истользуется в https://github.com/thinksaas/ThinkSAAS и др. (Китай)
