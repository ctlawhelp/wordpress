document.addEventListener('DOMContentLoaded', () => {
    const steps = document.querySelectorAll('.ig-step');
    const progress = document.querySelector('.ig-progress');
    let index = 0;

    function showStep(i) {
        steps.forEach((s, n) => {
            s.style.display = n === i ? 'block' : 'none';
        });
        const pct = ((i + 1) / steps.length) * 100;
        progress.style.width = pct + '%';
    }

    document.addEventListener('click', e => {
        if (e.target.matches('.next-step')) {
            index = Math.min(index + 1, steps.length - 1);
            showStep(index);
        }
    });

    showStep(index);
});
