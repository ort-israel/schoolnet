/**
 * Created by tsofiyai on 18/01/15.
 */

YUI().use('node', 'event', function (Y) {
    /* Stretch the blocks region to the bottom of the page*/

    var header = Y.one('header.navbar'),
        header_height = parseInt(header.getComputedStyle('height')) + parseInt(header.getStyle('padding-top')) + parseInt(header.getStyle('padding-bottom')),
        window_height = Y.one('window').get('winHeight'),
        page_height = parseInt(Y.one('#page').getComputedStyle('height')),
        calc_height = window_height - header_height,
        aside = Y.one('.span3');

    console.log(1234);
    if(page_height > calc_height)
    {
        aside.setStyle("height",page_height);
    }
    else
    {
        aside.setStyle("height",calc_height);
    }

    /* The titles of the badges should not go to badeges' page*/
    var badges_block = Y.one('.block_badges'),
        links;
    if(badges_block){
        links = badges_block.all('a')
        links.on('click', function(e){
            e.preventDefault();
        });
    }

});
