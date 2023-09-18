(function ($){

    $(document).ready(function (){
        const articleId = get_article_id();
        const articleTitle = get_article_title();
        const articleBody = $('div[itemprop="articleBody"]');
        
        if (articleBody.length === 0) return;

        $('<div>', {
            id: 'ja-attachment-response'
        }).insertBefore(articleBody);
        
        fetch_data(articleId, articleTitle);
    })
    
    function get_article_id(){
        const baseArticle = $('base', $('head')).first();

        if (baseArticle.length === 0) return;
        
        const articleLink = baseArticle.attr('href');
        const idMatch = articleLink.match(/\/(\d+)-[\w-]+$/);
        if (idMatch){
            return idMatch[1];
        }
        return 0;
    }

    function get_article_title(){
        var articleTitle = $('title', $('head')).first();
        if (articleTitle.text().length === 0) return;
        const article_title_full = articleTitle.text().replace(/PERI - /g, '');
        return article_title_full;
    }

    function fetch_data(articleId, articleTitle){
        const baseUrl = Joomla.getOptions('system.paths').base;
        const urlParams = {
            option: 'com_ajax',
            plugin: 'jak2tocomcontentmigration',
            format: 'json',
            jatask: 'fetchJoomlaAttachment',
            articleId: articleId,
            articleTitle: encodeURIComponent(articleTitle),
        };
        const paramsString = new URLSearchParams(urlParams).toString();
        const queryString = baseUrl + '/index.php?' + paramsString.replace(/%2C/g, ',');
        $.ajax({
            url: queryString,
            type: 'json',
            success: (data) => {
                var data_ = data.data[0];
                if (data_.code === 404){
                    console.log(data_.message);
                }else{
                    $('#ja-attachment-response').html(data_.data);
                    $('#ja-attachment-response').css('display', 'inline-block');
                }
            }
        })
    }

})(jQuery)