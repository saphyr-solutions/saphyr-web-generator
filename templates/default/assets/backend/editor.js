
function SaphyrEditor(editor) {
    var backendUrl='https://'+editor.client+'.saphyr-solutions.ch';


    // Tous les éléments éditables
    var EditorElements = document.querySelectorAll('[data-editor]');
    // Sections de la page
    var SectionsElements=Array.from(EditorElements).filter(function (el)
    {
        item = JSON.parse(el.dataset.editor);
        return item.module_slug=='sections';
    });

    // Elements isolés qui ne sont pas trouvés dans les sections & blocs
    // A améliorer...
    var SingleElements=Array.from(EditorElements).filter(function (el)
    {
        item = JSON.parse(el.dataset.editor);

        return item.module_slug!='sections' && item.module_slug!='blocs';
    });

    if (SectionsElements.length) SingleElements=[];



    var btns = {
        'logout': '<svg style="width:16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" ><title>Déconnection</title><path fill="red" d="M497 273L329 441c-15 15-41 4.5-41-17v-96H152c-13.3 0-24-10.7-24-24v-96c0-13.3 10.7-24 24-24h136V88c0-21.4 25.9-32 41-17l168 168c9.3 9.4 9.3 24.6 0 34zM192 436v-40c0-6.6-5.4-12-12-12H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h84c6.6 0 12-5.4 12-12V76c0-6.6-5.4-12-12-12H96c-53 0-96 43-96 96v192c0 53 43 96 96 96h84c6.6 0 12-5.4 12-12z" class=""></path></svg>',
        'open': '<svg style="width:16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" ><path fill="currentColor" d="M223.7 239l136-136c9.4-9.4 24.6-9.4 33.9 0l22.6 22.6c9.4 9.4 9.4 24.6 0 33.9L319.9 256l96.4 96.4c9.4 9.4 9.4 24.6 0 33.9L393.7 409c-9.4 9.4-24.6 9.4-33.9 0l-136-136c-9.5-9.4-9.5-24.6-.1-34zm-192 34l136 136c9.4 9.4 24.6 9.4 33.9 0l22.6-22.6c9.4-9.4 9.4-24.6 0-33.9L127.9 256l96.4-96.4c9.4-9.4 9.4-24.6 0-33.9L201.7 103c-9.4-9.4-24.6-9.4-33.9 0l-136 136c-9.5 9.4-9.5 24.6-.1 34z" class=""></path></svg>',
        'file': '<svg style="height:16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" ><title>Editer la page</title><path  d="M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3 0 24-10.7 24-24V160H248c-13.2 0-24-10.8-24-24zm160-14.1v6.1H256V0h6.1c6.4 0 12.5 2.5 17 7l97.9 98c4.5 4.5 7 10.6 7 16.9z" ></path></svg>',
        'fileEdit': '<svg style="height:16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" ><title>Ajouter une page</title><path d="M224 136V0H24C10.7 0 0 10.7 0 24v464c0 13.3 10.7 24 24 24h336c13.3 0 24-10.7 24-24V160H248c-13.2 0-24-10.8-24-24zm65.18 216.01H224v80c0 8.84-7.16 16-16 16h-32c-8.84 0-16-7.16-16-16v-80H94.82c-14.28 0-21.41-17.29-11.27-27.36l96.42-95.7c6.65-6.61 17.39-6.61 24.04 0l96.42 95.7c10.15 10.07 3.03 27.36-11.25 27.36zM377 105L279.1 7c-4.5-4.5-10.6-7-17-7H256v128h128v-6.1c0-6.3-2.5-12.4-7-16.9z" class=""></path></svg>',
        'section': '<svg style="width:16px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" ><title>{title}</title><path  d="M243.6 256a19.59 19.59 0 0 0-19.6 19.6v24.8a19.59 19.59 0 0 0 19.6 19.6h88.8a19.59 19.59 0 0 0 19.6-19.6v-24.8a19.59 19.59 0 0 0-19.6-19.6zM160 64h176v64a16 16 0 0 0 16 16h64v64h64v-76.06a48.16 48.16 0 0 0-14.09-34L382 14.09A48 48 0 0 0 348.09 0H144a48.14 48.14 0 0 0-48 48.07V208h64zm400 192H432a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h128a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zM416 448H160v-80H96v96a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48v-96h-64zM160 304v-32a16 16 0 0 0-16-16H16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h128a16 16 0 0 0 16-16z" class=""></path></svg>',
        'sections': '<svg style="width:16px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" ><title>{title}</title><path  d="M243.6 256a19.59 19.59 0 0 0-19.6 19.6v24.8a19.59 19.59 0 0 0 19.6 19.6h88.8a19.59 19.59 0 0 0 19.6-19.6v-24.8a19.59 19.59 0 0 0-19.6-19.6zM160 64h176v64a16 16 0 0 0 16 16h64v64h64v-76.06a48.16 48.16 0 0 0-14.09-34L382 14.09A48 48 0 0 0 348.09 0H144a48.14 48.14 0 0 0-48 48.07V208h64zm400 192H432a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h128a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zM416 448H160v-80H96v96a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48v-96h-64zM160 304v-32a16 16 0 0 0-16-16H16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h128a16 16 0 0 0 16-16z" class=""></path></svg>',
        'plus': '<svg style="width:16px"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 122.88 119.8"><title>{title}</title><path d="M23.59,0h75.7a23.63,23.63,0,0,1,23.59,23.59V96.21A23.64,23.64,0,0,1,99.29,119.8H23.59a23.53,23.53,0,0,1-16.67-6.93l-.38-.42A23.49,23.49,0,0,1,0,96.21V23.59A23.63,23.63,0,0,1,23.59,0ZM55.06,38.05a6.38,6.38,0,1,1,12.76,0V53.51H83.29a6.39,6.39,0,1,1,0,12.77H67.82V81.75a6.38,6.38,0,0,1-12.76,0V66.28H39.59a6.39,6.39,0,1,1,0-12.77H55.06V38.05ZM99.29,12.77H23.59A10.86,10.86,0,0,0,12.77,23.59V96.21a10.77,10.77,0,0,0,2.9,7.37l.28.26A10.76,10.76,0,0,0,23.59,107h75.7a10.87,10.87,0,0,0,10.82-10.82V23.59A10.86,10.86,0,0,0,99.29,12.77Z"/></svg>',
        'edit': '<svg style="width:16px"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 122.88 121.51"><title>{title}</title><path d="M28.66,1.64H58.88L44.46,16.71H28.66a13.52,13.52,0,0,0-9.59,4l0,0a13.52,13.52,0,0,0-4,9.59v76.14H91.21a13.5,13.5,0,0,0,9.59-4l0,0a13.5,13.5,0,0,0,4-9.59V77.3l15.07-15.74V92.85a28.6,28.6,0,0,1-8.41,20.22l0,.05a28.58,28.58,0,0,1-20.2,8.39H11.5a11.47,11.47,0,0,1-8.1-3.37l0,0A11.52,11.52,0,0,1,0,110V30.3A28.58,28.58,0,0,1,8.41,10.09L8.46,10a28.58,28.58,0,0,1,20.2-8.4ZM73,76.47l-29.42,6,4.25-31.31L73,76.47ZM57.13,41.68,96.3.91A2.74,2.74,0,0,1,99.69.38l22.48,21.76a2.39,2.39,0,0,1-.19,3.57L82.28,67,57.13,41.68Z"/></svg>',
        'blocs': '<svg style="width:16px"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 122.88 121.51"><title>{title}</title><path d="M28.66,1.64H58.88L44.46,16.71H28.66a13.52,13.52,0,0,0-9.59,4l0,0a13.52,13.52,0,0,0-4,9.59v76.14H91.21a13.5,13.5,0,0,0,9.59-4l0,0a13.5,13.5,0,0,0,4-9.59V77.3l15.07-15.74V92.85a28.6,28.6,0,0,1-8.41,20.22l0,.05a28.58,28.58,0,0,1-20.2,8.39H11.5a11.47,11.47,0,0,1-8.1-3.37l0,0A11.52,11.52,0,0,1,0,110V30.3A28.58,28.58,0,0,1,8.41,10.09L8.46,10a28.58,28.58,0,0,1,20.2-8.4ZM73,76.47l-29.42,6,4.25-31.31L73,76.47ZM57.13,41.68,96.3.91A2.74,2.74,0,0,1,99.69.38l22.48,21.76a2.39,2.39,0,0,1-.19,3.57L82.28,67,57.13,41.68Z"/></svg>',
        'faq': '<svg style="width:16px"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 122.88 121.51"><title>{title}</title><path d="M28.66,1.64H58.88L44.46,16.71H28.66a13.52,13.52,0,0,0-9.59,4l0,0a13.52,13.52,0,0,0-4,9.59v76.14H91.21a13.5,13.5,0,0,0,9.59-4l0,0a13.5,13.5,0,0,0,4-9.59V77.3l15.07-15.74V92.85a28.6,28.6,0,0,1-8.41,20.22l0,.05a28.58,28.58,0,0,1-20.2,8.39H11.5a11.47,11.47,0,0,1-8.1-3.37l0,0A11.52,11.52,0,0,1,0,110V30.3A28.58,28.58,0,0,1,8.41,10.09L8.46,10a28.58,28.58,0,0,1,20.2-8.4ZM73,76.47l-29.42,6,4.25-31.31L73,76.47ZM57.13,41.68,96.3.91A2.74,2.74,0,0,1,99.69.38l22.48,21.76a2.39,2.39,0,0,1-.19,3.57L82.28,67,57.13,41.68Z"/></svg>',
        'article':'<svg style="width:16px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><title>{title}</title><path d="M552 64H88c-13.255 0-24 10.745-24 24v8H24c-13.255 0-24 10.745-24 24v272c0 30.928 25.072 56 56 56h472c26.51 0 48-21.49 48-48V88c0-13.255-10.745-24-24-24zM56 400a8 8 0 0 1-8-8V144h16v248a8 8 0 0 1-8 8zm236-16H140c-6.627 0-12-5.373-12-12v-8c0-6.627 5.373-12 12-12h152c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12zm208 0H348c-6.627 0-12-5.373-12-12v-8c0-6.627 5.373-12 12-12h152c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12zm-208-96H140c-6.627 0-12-5.373-12-12v-8c0-6.627 5.373-12 12-12h152c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12zm208 0H348c-6.627 0-12-5.373-12-12v-8c0-6.627 5.373-12 12-12h152c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12zm0-96H140c-6.627 0-12-5.373-12-12v-40c0-6.627 5.373-12 12-12h360c6.627 0 12 5.373 12 12v40c0 6.627-5.373 12-12 12z" class=""></path></svg>',
        'articles':'<svg style="width:16px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><title>{title}</title><path d="M552 64H88c-13.255 0-24 10.745-24 24v8H24c-13.255 0-24 10.745-24 24v272c0 30.928 25.072 56 56 56h472c26.51 0 48-21.49 48-48V88c0-13.255-10.745-24-24-24zM56 400a8 8 0 0 1-8-8V144h16v248a8 8 0 0 1-8 8zm236-16H140c-6.627 0-12-5.373-12-12v-8c0-6.627 5.373-12 12-12h152c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12zm208 0H348c-6.627 0-12-5.373-12-12v-8c0-6.627 5.373-12 12-12h152c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12zm-208-96H140c-6.627 0-12-5.373-12-12v-8c0-6.627 5.373-12 12-12h152c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12zm208 0H348c-6.627 0-12-5.373-12-12v-8c0-6.627 5.373-12 12-12h152c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12zm0-96H140c-6.627 0-12-5.373-12-12v-40c0-6.627 5.373-12 12-12h360c6.627 0 12 5.373 12 12v40c0 6.627-5.373 12-12 12z" class=""></path></svg>'
    }

    var defaultBtn = '<svg style="width:16px"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 122.88 121.51"><title>{title}</title><path d="M28.66,1.64H58.88L44.46,16.71H28.66a13.52,13.52,0,0,0-9.59,4l0,0a13.52,13.52,0,0,0-4,9.59v76.14H91.21a13.5,13.5,0,0,0,9.59-4l0,0a13.5,13.5,0,0,0,4-9.59V77.3l15.07-15.74V92.85a28.6,28.6,0,0,1-8.41,20.22l0,.05a28.58,28.58,0,0,1-20.2,8.39H11.5a11.47,11.47,0,0,1-8.1-3.37l0,0A11.52,11.52,0,0,1,0,110V30.3A28.58,28.58,0,0,1,8.41,10.09L8.46,10a28.58,28.58,0,0,1,20.2-8.4ZM73,76.47l-29.42,6,4.25-31.31L73,76.47ZM57.13,41.68,96.3.91A2.74,2.74,0,0,1,99.69.38l22.48,21.76a2.39,2.39,0,0,1-.19,3.57L82.28,67,57.13,41.68Z"/></svg>';
    var menu = document.createElement('div');
    menu.className = 'adminMenu';

    menu.innerHTML = '<div style="display:flex;"><div class="actionBtn">' + btns.open + '</div><div class="menuBtn">'+( (SingleElements.length)?'<a class="addPage" href="javascript:void(0)">' + btns.fileEdit + '</a>': '<a class="editPage" href="javascript:void(0)">' + btns.file + '</a> <a class="addPage" href="#">' + btns.fileEdit + '</a>'+'<a class="addSection" href="javascript:void(0)">' + btns.sections.replace('{title}', 'Ajouter une section') + '</a>')+ '<a class="logout" href="javascript:void(0);">' + btns.logout + '</a></div></div>';
    openMenu = menu.querySelector('.actionBtn');
    openMenu.addEventListener('click', function (e) {

        if (menu.classList.contains('opened')) {
            menu.classList.remove('opened');
        } else {
            menu.classList.add('opened');
        }
    });
    sel = menu.querySelector('a.logout');
    sel.addEventListener('click', function (e) {
        e.stopPropagation();
        if (confirm('Terminer la session d\'édition')) {
            location.replace('/?edtMode=Osph22');
        }
        return false;
    });



    sel = menu.querySelector('a.addPage');
    sel.addEventListener('click', function (e) {
        e.stopPropagation();
        var link = '/module/'+editor.slug+'/list#formConfig/form/\\api\\v1\\module\\'+editor.id+'\\element\\formConfig';
        window.open(backendUrl + link, 'saphBackend');
        return false;
    });

    if (!SingleElements.length) {


        sel = menu.querySelector('a.editPage');
        sel.addEventListener('click', function (e) {
            e.stopPropagation();
            var link='/module/'+editor.slug+'/list#formConfig/form/\\api\\v1\\module\\'+editor.id+'\\element\\'+editor.edited+'\\formConfig';
            window.open(backendUrl + link, 'saphBackend');
            return false;
        });

        sel = menu.querySelector('a.addSection');
        if (sel) {
            sel.addEventListener('click', function (e) {
                if (editor.section) {
                    e.stopPropagation();
                    var link='/module/'+editor.slug+'/list#formConfig/view/\\api\\v1\\module\\'+editor.id+'\\element\\'+editor.edited+'\\formConfig|formConfig/form/\\api\\v1\\module\\'+editor.id+'\\element\\'+editor.edited+'\\'+editor.section+'\\formConfig';
                    window.open(backendUrl + link, 'saphBackend');
                }
                return false;
            });
        }
    }
    document.body.appendChild(menu);

    // On parse les articles
    if (SingleElements.length) {
        SingleElements.forEach(function (e) {
            var section = e;
            var childs = Array.from(e.querySelectorAll('[data-editor]')).filter(function (el)
            {
                item = JSON.parse(el.dataset.editor);
                return item.module_slug=='blocs';
            });
            var sectionConfig = JSON.parse(section.dataset.editor);
            var position = section.dataset.editorPosition;

            e.className = e.className + ' editor article';

            e.oncontextmenu = function (e) {
                return false;
            };
            var tpl = document.createElement('div');
            tpl.className = 'editor_container editor_'+(position);
            tpl.innerHTML = '<div class="inlinebtn"><a class="ArticleEdit" href="javascript:void(0)">' + btns[sectionConfig.module_slug].replace('{title}', 'Editer l\'article') + '</a></div>';

            sel = tpl.querySelector('a.ArticleEdit');
            sel.addEventListener('click', function (e) {
                e.stopPropagation();
                var link='/module/'+sectionConfig.module_slug+'/list#formConfig/form/\\api\\v1\\module\\'+sectionConfig.module_id+'\\element\\'+sectionConfig.id+'\\formConfig';
                window.open(backendUrl + link, 'saphBackend');
                return false;
            });



            e.appendChild(tpl);
            e.addEventListener('mouseover', function (ev) {
                ev.stopPropagation();
                section.classList.add("hover")
            });
            e.addEventListener('mouseout', function (ev) {
                ev.stopPropagation();
                section.classList.remove("hover")
            });
            e.addEventListener('mouseleave', function (ev) {
                ev.stopPropagation();
                section.classList.remove("hover")
            });

        });
     }


    SectionsElements.forEach(function (e) {

        var section = e;
        var childs = Array.from(e.querySelectorAll('[data-editor]')).filter(function (el)
        {
            item = JSON.parse(el.dataset.editor);
            return item.module_slug!='sections';
        });
        var sectionConfig = JSON.parse(section.dataset.editor);
        var position = section.dataset.editorPosition;
        e.className = e.className + ' editor section';

        e.oncontextmenu = function (e) {
            return false;
        };
        var tpl = document.createElement('div');
        tpl.className = 'editor_container editor_'+(position);
        tpl.innerHTML = '<div class="inlinebtn"><a class="sectionEdit" href="javascript:void(0)">' + btns[sectionConfig.module_slug].replace('{title}', 'Editer la section') + '</a> <a class="sectionAddBloc" href="javascript:void(0)">' + btns.plus.replace('{title}', 'Ajouter un bloc') + '</a></div>';

        sel = tpl.querySelector('a.sectionEdit');
        sel.addEventListener('click', function (e) {
            e.stopPropagation();
            var link='/module/'+sectionConfig.module_slug+'/list#formConfig/form/\\api\\v1\\module\\'+sectionConfig.module_id+'\\element\\'+sectionConfig.id+'\\formConfig';
            window.open(backendUrl + link, 'saphBackend');
            return false;
        });

        sel = tpl.querySelector('a.sectionAddBloc');
        sel.addEventListener('click', function (e) {
            e.stopPropagation();
            var link='/module/'+sectionConfig.module_slug+'/list#formConfig/view/\\api\\v1\\module\\'+sectionConfig.module_id+'\\element\\'+sectionConfig.id+'\\formConfig|formConfig/form/\\api\\v1\\module\\'+sectionConfig.module_id+'\\element\\'+sectionConfig.id+'\\'+sectionConfig.form+'\\formConfig';
            window.open(backendUrl + link, 'saphBackend');
            return false;
        });

        e.appendChild(tpl);
        e.addEventListener('mouseover', function (ev) {
            ev.stopPropagation();
            section.classList.add("hover")
        });
        e.addEventListener('mouseout', function (ev) {
            ev.stopPropagation();
            section.classList.remove("hover")
        });
        e.addEventListener('mouseleave', function (ev) {
            ev.stopPropagation();
            section.classList.remove("hover")
        });

        if (childs.length) {
            childs.forEach(function (e) {
                var child = e;
                child.oncontextmenu = function (e) {
                    return false;
                };
                child.className = e.className + ' editor';
                var childConfig = JSON.parse(child.dataset.editor);
                var position = child.dataset.editorPosition;

                var tpl = document.createElement('div');
                tpl.className = 'editor_container editor_'+(position);

                if (btns[childConfig.module_slug]) {
                    tpl.innerHTML = '<div class="inlinebtn"><a class="blocEdit" href="javascript:void(0)">' + btns[childConfig.module_slug].replace('{title}', 'Modifier') + '</a> </div>';
                } else {
                    tpl.innerHTML = '<div class="inlinebtn"><a class="blocEdit" href="javascript:void(0)">' + defaultBtn.replace('{title}', 'Modifier') + '</a> </div>';

                }

                sel = tpl.querySelector('a.blocEdit');
                sel.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var link='/module/'+childConfig.module_slug+'/list#formConfig/form/\\api\\v1\\module\\'+childConfig.module_id+'\\element\\'+childConfig.id+'\\formConfig';
                    window.open(backendUrl + link, 'saphBackend');

                    return false;
                });
                child.appendChild(tpl);
                child.addEventListener('mouseover', function (e) {
                    child.classList.add("hover")
                });
                child.addEventListener('mouseout', function (e) {
                    e.target.classList.remove("hover")
                });
                child.addEventListener('mouseleave', function (e) {
                    e.target.classList.remove("hover")
                });

            });
        }
    });

    // add empty class
    EditorElements.forEach(function (e) {
        if (e.offsetHeight === 0) {
            e.style.minHeight = "200px";
        }
    });
}
document.addEventListener('DOMContentLoaded', function () {
    SaphyrEditor(editor);
})

