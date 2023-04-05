# dokuwiki-plugin-imapmarkers

This software is a plugin for [DokuWiki](https://www.dokuwiki.org/).
You can make image maps with markers and links.

It is inspired by [dokuwiki-plugin-imagemap](https://github.com/i-net-software/dokuwiki-plugin-imagemap/), whose author, Gerry Weißbach, suggested me to write an own plugin for my purposes.
This plugin is a superset of the *dokuwiki-plugin-imagemap* plugin, simply use no references and empty area-identifiers.

This is a non-interactive sample of such a map:
![Acient World Map with Marker](https://raw.githubusercontent.com/kgitthoene/dokuwiki-plugin-imapmarkers/master/readme/map-with-marker.png)

Sourcecode in DokuWiki:
```
{{imapmarkers>imapmarkers:1744_bowen_map_of_the_world.jpg|Bowen Map of the World, 1744}}
[[https://en.wikipedia.org/wiki/Asia|CON1|Asia @ 456,116,30]]
[[https://en.wikipedia.org/wiki/Africa|CON2|Africa @ 358,167,30]]
[[https://en.wikipedia.org/wiki/North_America|CON3|North America @ 166,134,30]]
[[https://en.wikipedia.org/wiki/South_America|CON4|South America @ 222,210,30]]
[[https://en.wikipedia.org/wiki/Antarctica|CON5|Antarctica @ 152,324,30]]
[[https://en.wikipedia.org/wiki/Europe|CON6|Europe @ 381,110,30]]
[[wp>Australia|CON7|Australia @ 510,232,30]]
[[wp>Australia|Australia II @ 510,232,60]]
[[|Australia III @ 510,232,90]]
{{cfg>}}
  {
    "marker" : "internal",
    "marker-color": "red",
    "clicked-reference-css": { "font-weight": "bold", "color": "red" }
    "area-fillColor": "ff0000",
    "area-fillOpacity": 0.2
  }
{{<cfg}}
{{<imapmarkers}}

{{imapmloc>CON1|Asia}} |
{{imapmloc>CON2|Africa}} |
{{imapmloc>CON3|North America}} |
{{imapmloc>CON4|South America}} |
{{imapmloc>CON5|Antarctica}} |
{{imapmloc>CON6|Europe}} |
{{imapmloc>CON7|Australia}}
```

## Usage and Syntax

### Create a Map with Areas

You start with ```{{imapmarkers>IMAGE-LINK|TITLE}}``` and end with ```{{<imapmarkers}}```.

```IMAGE-LINK``` is a normal DokuWiki image link, [see here](https://www.dokuwiki.org/images), this may be an DokuWiki-internal or external address (https://…).

```TITLE``` is the title for your image, technically the ```alt="TITLE"``` attribute of the ```img``` element.

Enclosed in this, you define no, one or multiple image areas, [see here](https://www.w3schools.com/html/html_images_imagemap.asp), with this special area definition:

**Area with identifier**: ```[[LINK|IDENTIFIER|TITLE@COORDINATES]]```

**Area without identifier**: ```[[LINK|TITLE@COORDINATES]]```

```LINK``` is an ordinary [DokuWiki-link](https://www.dokuwiki.org/link).
This may be an external, internal or interwiki link.
If the area or marker is clicked, this link is opened.
If the identifier is blank and you click the area, the area is shown until you click it again.

```IDENTIFIER``` is a page-unique identifier for this area.
This identifier is later used in a clickable element, say **reference**, to show the marker.
If the identifier is blank or omited you can't refer to it.
Identifiers are case sensitive.

```TITLE``` is the title of the area.
If you hover over the area, this title is shown.

```COORDINATES``` are the areas location on the image. Coordinates can have 3 integer values (circle), 4 integer values (rectangle) or 6 or more integer values (polygon). If you define a polygon the number of values must be divisible by 2.
Details: [see here](https://www.w3schools.com/html/html_images_imagemap.asp).

**Circle**: X,Y,RADIUS

**Rectangle**: LEFT,TOP,RIGHT,BOTTOM

**Polygon**: X1,Y1,X2,Y2,X3,Y3 (and so on …)

All coordinates you enter here are from your original image.

### Configuration

A configuration is inside the map definition and starts with ```{{cfg>}}``` and ends with ```{{<cfg}}```

Between this you define a [JSON](https://www.json.org/json-en.html) object.
This plugin tests, if it is correct JSON.

There are these configuration options:

**"marker"**: (string) ```"internal"``` -- Use the plugins-internal marker. This is the default, so no need to write this, if you are happy with the internal marker.

**"marker"**: (string) ```"LINK"``` -- Internal or external link to an image.

Example: ```"marker": "imapmarkers:marker.002.png"```

Example: ```"marker": "https://upload.wikimedia.org/wikipedia/commons/f/f2/678111-map-marker-512.png"```

**"marker-width"**: (positive number) -- Set the markers width to this value.

**"marker-height"**: (positive number) -- Set the markers height to this value.

**"marker-color"**: (string) ```"HTML-COLOR"``` -- Set the internal markers color to this value.

Example: ```"marker-color": "#FDEB00"```

**"clicked-reference-css"**: (JSON) ```JSON-OBJECT-WITH-CSS-DEFINITIONS```  -- The CSS definitions are applied to a reference, if you click the reference.

Example: ```"clicked-reference-css": { "font-weight": "bold", "color": "red" }```

**"area-fillColor"**: (string) "HTML-COLOR-HEXADECIMAL" -- Set the color of the hoverd area. Don't use a `#' before the hex-code.
Example: ```"ff0000"```

**"area-fillOpacity"**: (float) ```OPACITY-PERCENT``` -- Set the opacity of the hovered area.
The value must between 0 and 1.
```1``` is full opacity.
```0``` is no opcity, i.e. the hovered area is not shown.

Configuration-Example (Place this **inside** the map definition!):

```
{{cfg>}}
  {
    "marker-color": "red",
    "clicked-reference-css": { "font-weight": "bold", "color": "red" },
    "area-fillColor": "ff0000",
    "area-fillOpacity": 0.2
  }
{{<cfg}}
```

### Create References

References are spans, i.e. a piece of inline text.
And if you click a reference, the marker is shown in the middle of the area with the same **identifier**.

References can be places everywhere in the page.

**Reference**: ```{{imapmloc>IDENTIFIER|TEXT}}```

```IDENTIFIER``` refers to the **areas** identifier.
You may define multiple references for one identifier.

```TEXT``` ist the text shown in the page.

Example: ```{{imapmloc>CON1|Asia}}``` -- Refers to the area with ```CON1``` as identifier.

## Installation

Download all these files and install them in
```DOKUWIKI-ROOT/lib/plugins/imapmarkers/``` - if the folder is called different it
will not work!

Please refer to http://www.dokuwiki.org/extensions for additional info
on how to install extensions in DokuWiki.

## Used Software and Attribution

This plugin is based on [dokuwiki-plugin-imagemap](https://github.com/i-net-software/dokuwiki-plugin-imagemap/), [ImageMapster](http://www.outsharked.com/imagemapster/) and [PHP Simple HTML DOM Parser](https://sourceforge.net/projects/simplehtmldom/).

## License

[MIT](https://github.com/kgitthoene/dokuwiki-plugin-imapmarkers/blob/master/LICENSE.md)
