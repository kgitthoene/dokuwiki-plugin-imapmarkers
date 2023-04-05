/**
 * DokuWiki Plugin imapmarkers (Syntax Component)
 *
 * @license MIT https://en.wikipedia.org/wiki/MIT_License
 * @author  Kai Thoene <k.git.thoene@gmx.net>
 */
if (window.jQuery) {
  if (!jQuery.isFunction(jQuery.fn.mapster)) {
    // Load jQuery Imagemapster Library. SEE: http://www.outsharked.com/imagemapster/
    /* DOKUWIKI:include_once jquery.imagemapster.js */
  }

  globalThis[Symbol.for('imapmarkers_storage')] = (function () {
    var defaults = {
      'debug': false, // false = no debug on console
      'marker_color': "#00AEEF",
      'clicked-reference-css': { 'font-weight': 'bold', 'color': '#00AEEF' }
    };
    var a_maps = [];
    var a_areas = [];
    var a_references = [];
    var a_clicked_css_properties = [];
    var a_cfg = [];
    var resize_timeout = null;
    var a_last_clicked_id = [];
    var a_imap_div = [];
    var imap_div_timeout = null;
    var nr_startup_intervals = 0;
    var mapster_decoration_obj_default = {
      fillColor: 'ffffff',
      fillOpacity: 0.3,
      wrapClass: true,
      wrapCss: true,
      clickNavigate: true
    };

    function append_html_to_jquery_object(html, object) {
      object[0].insertAdjacentHTML(
        "beforeEnd",
        html
      );
    }  // function append_html_to_jquery_object

    function calc_marker_pos(coords) {
      let split_coords = coords.split(",");
      var x = 0;
      var y = 0;
      switch (split_coords.length) {
        case 3:  // circle
          x = parseInt(split_coords[0]);
          y = parseInt(split_coords[1]);
          break;
        case 4:  // rectangle
          x = Math.floor((parseInt(split_coords[0]) + parseInt(split_coords[2])) / 2);
          y = Math.floor((parseInt(split_coords[1]) + parseInt(split_coords[3])) / 2);
          break;
        default:
          if (split_coords.length >= 6) {
            var num_coords = 0;
            for (var i = 0; i < (split_coords.length - 1); i += 2) {
              x += parseInt(split_coords[i]);
              y += parseInt(split_coords[i + 1]);
              num_coords++;
            }
            x = Math.floor(x / num_coords);
            y = Math.floor(y / num_coords);;
          }
      }
      return [x, y];
    }  // function calc_marker_pos

    function get_id_from_string(s) {
      const a_ids = s.split(" ");
      if (a_ids.length >= 1) {
        if (a_ids[0].length > 0) {
          return String(a_ids[0]);
        }
      }
      return null;
    }  // function get_id_from_string

    function get_index_from_string_end(s) {
      return parseInt(s.replace(/^[^\d]*/, ""));
    }  // function get_index_from_string_end

    function find_area_by_jquery_id(id, imap_index = null) {
      try {
        if (imap_index === null) {
          for (var i = 0; i < a_areas.length; i++) {
            if (a_areas[i] !== undefined) {
              for (var j = 0; j < a_areas[i].length; j++) {
                const area = a_areas[i][j];
                if (area['id'] == id) {
                  return [i, area['area'], j];
                }
              }
            }
          }
        } else {
          if (a_areas[imap_index] !== undefined) {
            for (var j = 0; j < a_areas[imap_index].length; j++) {
              const area = a_areas[imap_index][j];
              if (area['id'] == id) {
                return [imap_index, area['area'], j];
              }
            }
          }
        }
      } catch (e) { console.error("EXCEPTION=" + e); }
      return [-1, null, -1];
    }  // function find_area_by_jquery_id

    return {
      // exported variables:
      defaults: defaults,
      a_maps: a_maps,
      a_areas: a_areas,
      a_references: a_references,
      a_clicked_css_properties: a_clicked_css_properties,
      a_cfg: a_cfg,
      resize_timeout: resize_timeout,
      a_last_clicked_id: a_last_clicked_id,
      a_imap_div: a_imap_div,
      imap_div_timeout: imap_div_timeout,
      nr_startup_intervals: nr_startup_intervals,
      mapster_decoration_obj_default: mapster_decoration_obj_default,
      // exported functions:
      append_html_to_jquery_object: append_html_to_jquery_object,
      calc_marker_pos: calc_marker_pos,
      get_id_from_string: get_id_from_string,
      get_index_from_string_end: get_index_from_string_end,
      find_area_by_jquery_id: find_area_by_jquery_id,
    };
  })();


  addEventListener("DOMContentLoaded", (event) => {
    (function ($) {
      var _g = globalThis[Symbol.for('imapmarkers_storage')];

      $('img[usemap].imapmarkers').mapster({
        fillColor: 'ffffff',
        fillOpacity: 0.3,
        wrapClass: true,
        wrapCss: true,
        clickNavigate: true
      });

      //SEE:https://www.geeksforgeeks.org/how-to-get-all-css-styles-associated-with-an-element-using-jquery/   
      $.fn.cssSpecific = function (str) {
        var ob = {};
        if (this.length) {
          var css = str.split(', ');
          var prms = [];
          for (var i = 0, ii = css.length; i < ii; i++) {
            prms = css[i].split(':');
            ob[$.trim(prms[0])] = $(this).css($.trim(prms[1] || prms[0]));
          }
        }
        return ob;
      };  // $.fn.cssSpecific

      function get_marker_width_and_height(id) {
        return [$(id).width(), $(id).height()];
      }  // function get_marker_width_and_height

      function do_marker_if_resize() {
        _g.a_imap_div.forEach((object, index) => {
          if (_g.a_last_clicked_id[index] != undefined) {
            let marker_id_jquery = "#imapmarkers-marker-" + index;
            let id = _g.a_last_clicked_id[index]['id'];
            if (_g.defaults['debug']) { console.log("RESIZE::[" + index + "] LAST CLICKED ID=" + id); }
            if ((_g.a_last_clicked_id[index]['id'] !== undefined) && $(marker_id_jquery).is(":visible")) {
              let area_index = _g.a_last_clicked_id[index]['area_index'];
              if (_g.defaults['debug']) { console.log("RESIZE::[" + index + "] AREA-INDEX=" + area_index); }
              try {
                let found_area = _g.a_areas[index][area_index]['area'];
                let coords = found_area.attr("coords");
                let xy = _g.calc_marker_pos(coords);
                let wh = get_marker_width_and_height(marker_id_jquery);
                $(marker_id_jquery).css({ top: xy[1] - wh[1] + 3, left: xy[0] - (wh[0] / 2) });
              } catch (e) { console.error("EXCEPTION=" + e); }
            }
          }
        });
      }  // do_marker_if_resize

      function do_resize() {
        $('img[usemap]').each(function () {
          let parent = $(this.offsetParent);
          let parentparent = $(parent).parent();
          $(this).mapster('resize', ($(this)[0].naturalWidth < parentparent.width()) ? $(this)[0].naturalWidth : parentparent.width());
        });
        do_marker_if_resize();
      }  // function do_resize

      $(window).resize(function () {
        if (_g.resize_timeout != null) { clearTimeout(_g.resize_timeout); }
        _g.resize_timeout = setTimeout(do_resize, 100);
      });

      let imap_do_main_function = function () {
        if (_g.defaults['debug']) { console.log("imapmarkers::START IMAGEMAPPING MARKER"); }
        _g.nr_startup_intervals++;
        if (_g.nr_startup_intervals >= 5) {
          // stop after 5 s searching:
          if (_g.imap_div_timeout !== null) { clearTimeout(_g.imap_div_timeout); }
          if (_g.defaults['debug']) { console.log("imapmarkers::GIVE UP IMAGEMAPPING SEARCH"); }
          return;
        }
        // find container:
        var imap_index = 0;
        $(".imapmarkers-container").each(function (index, object) {
          let imap_index = _g.get_index_from_string_end($(this).attr("id"));
          if (_g.a_imap_div[imap_index] === undefined) {
            if (_g.defaults['debug']) { console.log("[" + imap_index + "] CONTAINER FOUND ID='" + $(this).attr("id") + "'"); }
            _g.a_imap_div[imap_index] = $(this);
          }
        });
        if ((_g.a_imap_div[0] !== undefined) && (_g.a_imap_div[0] !== null)) {
          // resize image:
          do_resize();
          // find maps:
          $(".imapmarkers-map").each(function (index, object) {
            let imap_index = _g.get_index_from_string_end($(this).attr("name"));
            if (_g.a_maps[imap_index] === undefined) {
              _g.a_maps[imap_index] = $(this);
              if (_g.defaults['debug']) { console.log("[" + imap_index + "] MAP FOUND NAME='" + $(this).attr("name") + "'"); }
            }
          });
          // set img z-index and collect areas:
          _g.a_imap_div.forEach((object, index) => {
            let imap_div_index = index;
            if (_g.defaults['debug']) { console.log("[" + index + "] Z-INDEX THIS TAG=" + object.prop("tagName") + " ID=" + object.attr("id") + " NAME=" + object.attr("name")); }
            // set z-index for images:
            object.find("img").css("z-index", "0");
            // collect areas:
            if (_g.a_maps[index] !== undefined) {
              _g.a_maps[index].find("area").each(function (area_index, object) {
                if (_g.a_areas[index] === undefined) {
                  _g.a_areas[index] = [];
                }
                const area_id = $(this).attr("location_id");
                _g.a_areas[index].push({ 'id': area_id, 'area': $(this) });
                if (_g.defaults['debug']) { console.log("[" + index + "] ADD AREA #" + area_index + " ID='" + area_id + "'"); }
              });
            }
          });
          // find configurations:
          $(".imapmarkers-config").each(function (index, object) {
            try {
              let cfg_id = $(this).attr("id");
              let imap_index = _g.get_index_from_string_end(cfg_id);
              let cfg_text = $(this).text();
              cfg_text = cfg_text.replaceAll('„', '"').replaceAll('“', '"');
              let cfg = JSON.parse(cfg_text);
              if (imap_index >= 0) {
                _g.a_cfg[imap_index] = cfg;
                if (cfg['clicked-reference-css'] !== undefined) {
                  _g.a_clicked_css_properties[imap_index] = Object.keys(cfg['clicked-reference-css']).join(", ");
                  if (_g.defaults['debug']) { console.log("[" + imap_index + "] CLICKED CSS PROPERTIES='" + _g.a_clicked_css_properties[imap_index] + "'"); }
                }
                if (_g.defaults['debug']) { console.log("[" + imap_index + "] CFG FOUND ID=" + cfg_id + " CFG='" + cfg_text + "'"); }
                var is_changed = false;
                var mapster_decoration_obj = JSON.parse(JSON.stringify(_g.mapster_decoration_obj_default));
                if (cfg['area-fillColor'] != undefined) {
                  is_changed = true;
                  mapster_decoration_obj.fillColor = String(cfg['area-fillColor']);
                }
                if (cfg['area-fillOpacity'] != undefined) {
                  is_changed = true;
                  mapster_decoration_obj.fillOpacity = parseFloat(String(cfg['area-fillOpacity']));
                }
                if (is_changed) {
                  let img_jquery_id = "#imapmarkers-img-" + imap_index;
                  if (_g.defaults['debug']) { console.log("[" + imap_index + "] APPLY LOOK TO IMG-JQUERY-ID='" + img_jquery_id + "'"); }
                  $(img_jquery_id).mapster('set_options', mapster_decoration_obj);
                }
              } else {
                throw new Error("Id not found in any mapping! CONFIG-ID='" + cfg_id + "'");
              }
            } catch (e) {
              let msg = "EXCEPTION=" + e;
              console.error(msg);
              $(this).css("background-color", "#FF0000");
              $(this).append(' <span style="color:white; background-color:red;">' + msg + '</span>');
              $(this).show();
            }
          });
          // create marker:
          _g.a_imap_div.forEach((object, index) => {
            let imap_div_index = index;
            let marker_id = "imapmarkers-marker-" + index;
            let marker_id_jquery = "#" + marker_id;
            let is_marker_internal = true;
            if ((_g.a_cfg[imap_div_index] !== undefined) && (_g.a_cfg[imap_div_index]['marker'] !== undefined) && (String(_g.a_cfg[imap_div_index]['marker']).length > 0)) {
              let marker_loc = String(_g.a_cfg[imap_div_index]['marker']);
              if (marker_loc != "internal") {
                var marker_src;
                if (marker_loc.match(/^https{0,1}:\/\//) !== null) {
                  // http[s] URI:
                  marker_src = '<img id="' + marker_id + '" src="' + marker_loc + '">';
                } else {
                  // media link:
                  marker_src = '<img id="' + marker_id + '" src="/lib/exe/fetch.php?media=' + marker_loc + '">';
                }
                let imagemapster_div = _g.a_imap_div[imap_div_index].find("div.imapmarkers\\-image");
                _g.append_html_to_jquery_object(marker_src, imagemapster_div);
                if ((_g.a_cfg[imap_div_index] !== undefined) && (_g.a_cfg[imap_div_index]['marker-width'] !== undefined)) {
                  $(marker_id_jquery).css("width", parseInt(_g.a_cfg[imap_div_index]['marker-width']));
                }
                if ((_g.a_cfg[imap_div_index] !== undefined) && (_g.a_cfg[imap_div_index]['marker-height'] !== undefined)) {
                  $(marker_id_jquery).css("height", parseInt(_g.a_cfg[imap_div_index]['marker-height']));
                }
                is_marker_internal = false;
              }
            }
            if (is_marker_internal) {
              // create internal, default, marker:
              let svg_marker = '<svg version="1.1" id="' + marker_id + '" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 365 560" enable-background="new 0 0 365 560" xml:space="preserve"> ' +
                '<g> ' +
                '  <path fill="#00AEEF" d="M182.9,551.7c0,0.1,0.2,0.3,0.2,0.3S358.3,283,358.3,194.6c0-130.1-88.8-186.7-175.4-186.9 ' +
                '    C96.3,7.9,7.5,64.5,7.5,194.6c0,88.4,175.3,357.4,175.3,357.4S182.9,551.7,182.9,551.7z M122.2,187.2c0-33.6,27.2-60.8,60.8-60.8 ' +
                '    c33.6,0,60.8,27.2,60.8,60.8S216.5,248,182.9,248C149.4,248,122.2,220.8,122.2,187.2z"/> ' +
                '</g> ' +
                '</svg>';
              let imagemapster_div = _g.a_imap_div[imap_div_index].find("div.imapmarkers\\-image");
              _g.append_html_to_jquery_object(svg_marker, imagemapster_div);
              $(marker_id_jquery).css("width", 21);
              $(marker_id_jquery).css("height", 32);
              if ((_g.a_cfg[imap_div_index] !== undefined) && (_g.a_cfg[imap_div_index]['marker-width'] !== undefined)) {
                $(marker_id_jquery).css("width", parseInt(_g.a_cfg[imap_div_index]['marker-width']));
              }
              if ((_g.a_cfg[imap_div_index] !== undefined) && (_g.a_cfg[imap_div_index]['marker-height'] !== undefined)) {
                $(marker_id_jquery).css("height", parseInt(_g.a_cfg[imap_div_index]['marker-height']));
              }
              $(marker_id_jquery).css("top", -32);
              $(marker_id_jquery).css("left", -32);
              $("path", $(marker_id_jquery)).attr("style", "fill:" + _g.defaults['marker_color']);
              if ((_g.a_cfg[imap_div_index] !== undefined) && (_g.a_cfg[imap_div_index]['marker-color'] !== undefined)) {
                $("path", $(marker_id_jquery)).attr("style", "fill:" + _g.a_cfg[imap_div_index]['marker-color']);
              }
            }
            $(marker_id_jquery).css("position", "absolute");
            $(marker_id_jquery).css("z-index", 1000);
            $(marker_id_jquery).hide();
            if (_g.defaults['debug']) { console.log("[" + index + "] ADD MARKER ID='" + marker_id + "'"); }
          });
          // setup clicked css style keywords:
          _g.a_imap_div.forEach((object, index) => {
            try {
              if (_g.a_clicked_css_properties[index] === undefined) {
                _g.a_clicked_css_properties[index] = Object.keys(_g.defaults['clicked-reference-css']).join(", ");
                if (_g.defaults['debug']) { console.log("[" + index + "] CLICKED CSS PROPERTIES='" + _g.a_clicked_css_properties[index] + "'"); }
              }
            } catch (e) { console.error("EXCEPTION=" + e); }
          });
          // search for references:
          $(".imapmarkers-location, .wrap_imapmloc").each(function (index, object) {
            try {
              var loc_id = null;
              if ($(this).hasClass("imapmarkers-location")) {
                loc_id = $(this).attr("location_id");
                if (_g.defaults['debug']) { console.log("FOUND IMAPMLOC NORMAL ID='" + loc_id + "' FONT-WEIGHT=" + $(this).css("font-weight") + " COLOR=" + $(this).css("color")); }
              }
              if ($(this).hasClass("wrap_imapmloc")) {
                loc_id = _g.get_id_from_string($(this).text());
                if (_g.defaults['debug']) { console.log("FOUND IMAPMLOC WRAP ID='" + loc_id + "' FONT-WEIGHT=" + $(this).css("font-weight") + " COLOR=" + $(this).css("color")); }
              }
              if (loc_id !== null) {
                // find corresponding area
                let ia = _g.find_area_by_jquery_id(loc_id);
                //if (_g.defaults['debug']) { console.log("SEARCH AREA -> '" + JSON.stringify(ia) + "'"); }
                let imap_index = ia[0];
                let found_area = ia[1];
                let area_index = ia[2];
                if (found_area != null) {
                  if (_g.a_references[imap_index] === undefined) {
                    _g.a_references[imap_index] = [];
                  }
                  _g.a_references[imap_index].push({ 'id': loc_id, 'imap_index': imap_index, 'area_index': area_index, 'reference': $(this), 'css': $(this).cssSpecific(_g.a_clicked_css_properties[imap_index]) });
                  let reference_index = _g.a_references[imap_index].length - 1;
                  if (_g.defaults['debug']) { console.log("FOUND AREA FOR IMAPMLOC ID='" + loc_id + "' AREA IMAP-INDEX=" + imap_index + " AREA-INDEX=" + area_index + " CSS='" + JSON.stringify($(this).cssSpecific(_g.a_clicked_css_properties[imap_index])) + "'"); }
                  $(this).on("click", { 'area': found_area, 'id': loc_id, 'imap_index': imap_index, 'area_index': area_index, 'reference_index': reference_index }, function (e) {
                    var data = e.data;
                    let imap_index = data['imap_index'];
                    let marker_id_jquery = "#imapmarkers-marker-" + imap_index;
                    let area_index = data['area_index'];
                    let id = data['id'];
                    let reference_index = data['reference_index'];
                    if (_g.defaults['debug']) { console.log("CLICK ID='" + id + "' AREA IMAP-INDEX=" + imap_index + " AREA-INDEX=" + area_index); }
                    if ((_g.a_last_clicked_id[imap_index] !== undefined) && (_g.a_last_clicked_id[imap_index]['id'] == data['id']) && $(marker_id_jquery).is(":visible")) {
                      // hide marker
                      $(marker_id_jquery).hide();
                      $(marker_id_jquery).css('cursor', '');
                      _g.a_references[imap_index].forEach((object, index) => {
                        if (object['id'] == id) {
                          object['reference'].css(object['css']);
                        }
                      });
                      $(this).css(_g.a_references[imap_index][reference_index]['css']);
                    } else {
                      // show marker
                      let coords = data['area'].attr("coords");
                      let xy = _g.calc_marker_pos(coords);
                      let wh = get_marker_width_and_height(marker_id_jquery);
                      $(marker_id_jquery).css({ top: xy[1] - wh[1] + 3, left: xy[0] - (wh[0] / 2) });
                      let href = _g.a_areas[imap_index][area_index]['area'].attr('href');
                      if (String(href).length > 0) {
                        $(marker_id_jquery).css('cursor', 'pointer');
                        $(marker_id_jquery).on("click", { 'imap_index': imap_index, 'area_index': area_index }, function () {
                          let href = _g.a_areas[imap_index][area_index]['area'].attr('href');;
                          if (_g.defaults['debug']) { console.log("[" + imap_index + "] CLICK ON MARKER HREF='" + href + "'"); }
                          window.location.href = href;
                        });
                      }
                      $(marker_id_jquery).show();
                      _g.a_references[imap_index].forEach((object, index) => {
                        if (object['id'] == id) {
                          if ((_g.a_cfg[imap_index] !== undefined) && (_g.a_cfg[imap_index]['clicked-reference-css'] !== undefined)) {
                            object['reference'].css(_g.a_cfg[imap_index]['clicked-reference-css']);
                          } else {
                            object['reference'].css(_g.defaults['clicked-reference-css']);
                          }
                        } else {
                          object['reference'].css(object['css']);
                        }
                      });
                    }
                    if (_g.defaults['debug']) { console.log("[" + imap_index + "] LAST CLICKED ID='" + data['id'] + " MARKER-ID='" + marker_id_jquery + "'"); }
                    _g.a_last_clicked_id[imap_index] = { 'imap_index': imap_index, 'area_index': area_index, 'id': data['id'] };
                  });
                  $(this).css('cursor', 'pointer');
                }
              }
            } catch (e) { console.error("EXCEPTION=" + e); }
          });
        } else {
          if (_g.imap_div_timeout !== null) { clearTimeout(_g.imap_div_timeout); }
          _g.imap_div_timeout = setTimeout(imap_do_main_function, 1000);
        }
      };

      _g.imap_div_timeout = setTimeout(imap_do_main_function, 1000);
    })(jQuery);
  });

  function addBtnActionImagemap(btn, props, edid) {
    // Not implemented yet
  }
}