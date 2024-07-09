require(['jquery'], function($) {
  const recordShare = (platform, objectId) => {			
    $.ajax({
      url: socialshareUrl,
      type: 'POST',
      data: JSON.stringify({ platform, objectId, action: 'contest-sharing' }),
      contentType: 'application/json'
    });
  }

  // Close Modal for report
  $('.closeModalFlag').click(function(){
    // Hide modal 
    $('#dialogFlag').addClass('hidden');

    // Show the participation dialog
    $('#dialogParticipation').removeClass('hidden');
  });  

  // Submit participation form (like post)
  $(document).on("click", '#participationForm button[type="submit"], #participationForm .canLike', function(e) {
    e.preventDefault();

    $(document).find('#participationForm').submit();
  });

  $(document).on("click", '.socials_links a', function() {
    const platform = $(this).attr("title");
    const objectId = $(this).closest('ul').attr('data-objectid');

    recordShare(platform, objectId);
  });
});
