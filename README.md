# Stamper

Prototype for Vue.js-style components for PHP

## Rationale

There's far too much Javascript on the web these days requiring multi-megabyte bundles
that can become far too cumbersome to develop, and are heavy on the users' web browsers.
There are, however, some niceties from frontend tooling such as componentized templates.
This project aims to bring these to PHP so that you can have a familiar, quick development
experience with server-side rendered pages.

## There be dragons ahead

Because this is a prototype you probably don't want to use it for any of your production
projects. It has issues such as not XSS escaping and will likely break in interesting ways
when you try to use it in a proper project. Feel free to contribute to try to bring it up to
a 1.0 release.

## Installation

```bash
composer require icosillion/stamper
```

## Constructs

### If / Else

```html
<div s-if="film.rating > 4">This is a well rated film!</div>
<div s-else>This film had mixed reviews</div>
```

### For

```html
<ul>
    <li s-for="films as film">{{film.name}}</li>
</ul>
```

### Components

**Registering a component**
```php
$stamper = new Stamper();
$stamper->registerComponent('warning', __DIR__ . '/warning.html');
```

**Example**
```html
<div>
    <warning data-type="error">
        Something went wrong
    </warning>
</div>
```

**Example component**
```html
<template>
    <div class="warning {{props.type}}">
        {{ props.type }}: {{children}}
    </div>
</template>

<style>
    .warning {
        background: yellow;
    }

    .warning.error {
        background: red;
    }
</style>
```

### Component props

In Stamper there are two different types of props `data-` props and `data-s` props.
`data-` props just contain strings and nothing else. They behave exactly as you would
expect a data prop to behave. On the other hand `data-s-` props support code and
passing around expressions. Any property that has an `s-` prefix supports code within
stamper.

### Expression language

Stamper uses Symfony's [Expression Language](https://symfony.com/doc/current/components/expression_language.html)
for evaluating code within your templates. It also has a few extension methods that you
can use in your templates:

* `isset('variable')`
* `count(variable)`
