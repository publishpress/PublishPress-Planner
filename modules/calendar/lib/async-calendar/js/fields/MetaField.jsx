import DOMPurify from 'dompurify';
const $ = jQuery;

export default function MetaField(props) {
    
    const editField = () => {

        if ($('.pp_editorial_single_select2').length > 0) {
            $('.pp_editorial_single_select2').pp_select2(
              {
                allowClear: true,
                placeholder: function(){
                  $(this).data('placeholder');
                }
              }
            );
          }
        
          if ($('.pp_editorial_meta_multi_select2').length > 0) {
            $('.pp_editorial_meta_multi_select2').pp_select2({
              multiple: true
            });
          }
        
        return (
            <div
                dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(props.html) }}>
            </div>
        )
    }

    const viewField = () => {
        return (
            <span id={props.id}>{props.value}</span>
        );
    }

    return props.isEditing ? editField() : viewField();
}
