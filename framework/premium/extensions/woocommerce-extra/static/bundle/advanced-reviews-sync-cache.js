!function(){"use strict";function t(t,e){(null==e||e>t.length)&&(e=t.length);for(var n=0,r=new Array(e);n<e;n++)r[n]=t[n];return r}function e(e,n){if(e){if("string"==typeof e)return t(e,n);var r=Object.prototype.toString.call(e).slice(8,-1);return"Object"===r&&e.constructor&&(r=e.constructor.name),"Map"===r||"Set"===r?Array.from(e):"Arguments"===r||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?t(e,n):void 0}}function n(t,n){return function(t){if(Array.isArray(t))return t}(t)||function(t,e){var n=null==t?null:"undefined"!=typeof Symbol&&t[Symbol.iterator]||t["@@iterator"];if(null!=n){var r,o,a=[],c=!0,i=!1;try{for(n=n.call(t);!(c=(r=n.next()).done)&&(a.push(r.value),!e||a.length!==e);c=!0);}catch(t){i=!0,o=t}finally{try{c||null==n.return||n.return()}finally{if(i)throw o}}return a}}(t,n)||e(t,n)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function r(n){return function(e){if(Array.isArray(e))return t(e)}(n)||function(t){if("undefined"!=typeof Symbol&&null!=t[Symbol.iterator]||null!=t["@@iterator"])return Array.from(t)}(n)||e(n)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}(0,window.ctFrontend.registerDynamicChunk)("blocksy_ext_woo_extra_advanced_reviews_sync_cache",{mount:function(t){var e=t.querySelectorAll(".ct-review-vote");if(e.length){var o=r(new Set(Array.from(e).map((function(t){return+t.dataset.commentId}))));fetch("".concat(ct_localizations.ajax_url,"?action=ct_sync_votes"),{method:"POST",body:JSON.stringify({comments_ids:o}),headers:{Accept:"application/json","Content-Type":"application/json"}}).then((function(t){return t.json()})).then((function(t){var e=t.votes,r=t.user;Object.entries(e).forEach((function(t){var e=n(t,2),o=e[0],a=e[1];if(a){var c=document.querySelector('.ct-review-vote[data-comment-id="'.concat(o,'"][data-vote="up"]')),i=document.querySelector('.ct-review-vote[data-comment-id="'.concat(o,'"][data-vote="down"]'));if(c&&i){var u=a.up,l=void 0===u?[]:u,s=a.down,d=void 0===s?[]:s;l.includes(r)&&(c.dataset.buttonState="active",i.dataset.buttonState=""),d.includes(r)&&(c.dataset.buttonState="",i.dataset.buttonState="active");var f=c.closest(".ct-review-votes"),v=f.querySelector(".ct-review-total-count"),y=f.querySelector(".ct-review-upvote-count");f.querySelector(".ct-review-vote-count").dataset.count=l.length,v.innerHTML=l.length+d.length,y.innerHTML=l.length}}}))}))}}})}();