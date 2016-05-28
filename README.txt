Now this module supports only one case - tagging of the files with type image.
In terms of Google Cloud Vision API it's label detection functionality.

Step by step instruction how to configure it:
1. Enable the module and set API key on the page /admin/config/media/google_vision.
2. Create taxonomy vocabulary for your labels (tags).
3. Add new field into your file entity (can be done through module file_entity)
  This field should be reference to your taxonomy vocabulary and enable the option "Enable Google Vision"
4. Now each time when you will create/update your file (image)
  the module google vision will add labels for you file into your field.

Other features are coming soon but you can help me through feature requests and bug reports.