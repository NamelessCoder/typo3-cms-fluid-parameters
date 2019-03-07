Parameterised Templates/Sections for Fluid on TYPO3 CMS
=======================================================

Installing this package as TYPO3 extension allows you to declare arguments for a partial or section in a partial, like
you would register arguments inside a ViewHelper class, and triggering the exact same kind of validation of arguments.

It is a limited-capability proof of concept of point no. 3 from https://github.com/TYPO3/Fluid/issues/424. The final
implementation of this feature will not have the limitations this package has.

And it is future signature compatible with the envisioned feature for Fluid with a single exception (see below).


Example
-------

```xhtml
<!-- MyPartial.html -->
<f:parameter name="partialParameterOne" type="bool" description="Required parameter" 
    required="1" />
<f:parameter name="partialParameterTwo" type="string" description="Optional parameter with default" 
    required="0" default="Default Value" />

<f:section name="MySection">
    <f:parameter name="sectionParameter" type="int" description="Section parameter"
        required="0" default="Bar" forSection="MySection" />
    Section parameter value: {sectionParameter}

</f:section>

<h4>Partial rendered!</h4>
<ul>
    <li>Value of "partialParameterOne" is {partialParameterOne}</li>
    <li>Value of "partialParameterTwo" is {partialParameterTwo}</li>
    <li>Rendering MySection yielded: <f:render section="MySection" /></li>
</ul>
```

This example declares two parameters for the Partial itself:

1. `partialParameterOne` which is required and must be a boolean
2. `partialParameterTwo` which is optional and has "Default Value" as default value

And one section with one parameter, `sectionParameter`, which is optional and has "Bar" as default value 


What happens when rendering
---------------------------

Given the example above, if you call `<f:render partial="MyPartial" />` without passing any arguments, you will see a
"required argument not provided" exception just like had you called a ViewHelper and left out a mandatory argument.

However, if you call `<f:render partial="MyPartial" arguments="{partialParameterOne: 'My value'}" />` then the required
argument is present and the Partial will be allowed to render. But since you did not pass `partialParameterTwo` and this
argument is optional, instead of throwing an error, a default value is assigned as argument value.

Lastly, when you call `<f:render section="MySection" />` from within the Partial template - or when you call
`<f:render partial="MyPartial" section="MySection" />` from anywhere - the parameters defined inside the section will
be used to validate and pad missing values using default value if one is defined.


Limitations (compared to final implementation in Fluid)
-------------------------------------------------------

Because this package opts for the least invasive solution and avoids XCLASS'ing and uses a minimum amount of custom
implementations there are a couple of limitations compared to the final version of the feature which will be in Fluid:

1. The support for parameters is limited to partials and sections inside partials. Specifying parameters for sections
   elsewhere issimply not supported and will cause an error to be raised. This is due to not being able to "inject" the
   necessary code that would need to be placed inside internal Fluid logic.
2. The `RenderViewHelper` from TYPO3 CMS will be overridden causing the class name to be different when resolved. The
   substitute class is fully signature compatible but this override does mean the package is potentially incompatible
   with other packages that also override this ViewHelper.
3. Access has to be forced to two currently `protected` methods in Fluid:
   * `$node->getUinitializedViewHelper()` instance holds our `$renderingContext` but has no getter so access is forced.
   * `$view->getCurrentParsedTemplate()` method is `protected` and access is forced.
   These two instances are the only API violations and are only necessary because of the "no overrides" strategy. They
   are however completely trustworthy in terms of holding the right value at the right time.
4. Contrary to the final implementation this limited implementation requires you to specify a `forSection` argument
   on `f:parameter` when the parameter must apply to a specific section (in that template). This means that you can
   technically put all `f:parameter` calls outside of templates as the nesting won't matter. The final implementation
   will *not* require this argument and will instead be sensitive as to where it is placed (in section or outside).
   
The solution is signature compatible with the exception of the `forSection` parameter. Once the final implementation is
complete this package will be marked as incompatible with any Fluid version at or above the version that includes it,
at which time you would then have to edit all templates to remove the `forSection` argument.

Despite being able to place `f:paramter` outside of `f:section` and provide `forSection` it is highly recommended to
place it inside the `f:section` since the final implementation will be hierarchy-sensitive. It also minimises the effort
needed to migrate to the final implementation.
