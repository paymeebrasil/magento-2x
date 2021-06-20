var config = {
    paths: {            
            'jquery_mask': "js/jquery.mask.latest",
            'clipboard': 'Paymee_Pix/js/clipboard',
        },   
    shim: {
        'jquery_mask': {
            deps: ['jquery']
        },
        'clipboard': {
            exports: 'ClipboardJS',
            deps: ['jquery']
        },
    },
};