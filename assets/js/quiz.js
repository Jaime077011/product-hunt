document.addEventListener('DOMContentLoaded', function () {
    const quizForm = document.getElementById('product-hunt-quiz-form');
    if (quizForm) {
        quizForm.addEventListener('submit', function (event) {
            event.preventDefault();
            submitQuiz();
        });
    }
});

function submitQuiz() {
    const quizForm = document.getElementById('product-hunt-quiz-form');
    const formData = new FormData(quizForm);
    
    // Show loading state
    document.getElementById('quiz-submit-button').disabled = true;
    document.getElementById('quiz-loading').style.display = 'block';
    
    // Get the correct AJAX URL from the localized script
    const ajaxUrl = product_hunt_vars.ajax_url;
    console.log('Submitting to:', ajaxUrl);
    
    fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Quiz submission response:', data);
        
        if (data.success) {
            // Success handling
            if (data.data && data.data.redirect) {
                window.location.href = data.data.redirect;
            } else {
                document.getElementById('quiz-result').innerHTML = 'Quiz submitted successfully!';
                document.getElementById('quiz-result').style.display = 'block';
            }
        } else {
            // Error handling
            document.getElementById('quiz-result').innerHTML = 'Error: ' + (data.data || 'Unknown error');
            document.getElementById('quiz-result').style.display = 'block';
            document.getElementById('quiz-result').classList.add('error');
        }
    })
    .catch(error => {
        console.error('Error submitting quiz:', error);
        document.getElementById('quiz-result').innerHTML = 'Error: ' + error.message;
        document.getElementById('quiz-result').style.display = 'block';
        document.getElementById('quiz-result').classList.add('error');
    })
    .finally(() => {
        // Reset loading state
        document.getElementById('quiz-submit-button').disabled = false;
        document.getElementById('quiz-loading').style.display = 'none';
    });
    
    return false; // Prevent form submission
}