import gulp from "gulp";
const { src, dest, watch, series, parallel } = gulp;
import uglify from 'gulp-uglify';
import gulpSass from "gulp-sass";
import autoPrefixer from "gulp-autoprefixer";
import * as sass from "sass";

const scss = gulpSass(sass);

const THEMES = ["business", "elearning", "onlinetutoring", "wellness"];


function compileThemeCss(theme) {
    return function () {
        return src(`scss/themes/${theme}/frontend.scss`)
            .pipe(scss({ outputStyle: "compressed" }))
            .pipe(autoPrefixer())
            .pipe(dest(`application/views/css/themes/${theme}/`));
    };
}

function css() {
    var common = src('scss/common.scss')
            .pipe(scss({ outputStyle: "compressed" }))
            .pipe(autoPrefixer())
            .pipe(dest('application/views/css'))
                .pipe(dest('dashboard/views/css'));       

    var dashboard = src('scss/dashboard.scss')
            .pipe(scss())
            .pipe(autoPrefixer())
                .pipe(dest('dashboard/views/css'));
        
   var manager = src('scss/manager.scss')
            .pipe(scss())
            .pipe(autoPrefixer())
                .pipe(dest('manager/views/css'));
        
    var course = src('scss/course-personal.scss')
            .pipe(scss())
            .pipe(autoPrefixer())
                .pipe(dest('dashboard/views/css'));

    var quiz = src('scss/quiz.scss')
            .pipe(scss())
            .pipe(autoPrefixer())
                .pipe(dest('dashboard/views/css'));
        
    var forum = src('scss/forum.scss')
            .pipe(scss({ outputStyle: "compressed" }))
            .pipe(autoPrefixer())
            .pipe(dest('application/views/css'));
    return (common, dashboard, manager, course, quiz, forum);
}

function minifyjs() {
    return src("application/views/common-js-src/*.js", {
        allowEmpty: true,
    })
        .pipe(uglify())
        .pipe(dest("application/views/common-js"));
}

const cssTasks = THEMES.map((theme) => compileThemeCss(theme));

// Watch files
function watchFiles() {
    watch(["scss"], css);
    watch(["application/views/common-js-src"], minifyjs);
    THEMES.forEach((theme) => {
        watch([`scss/themes/${theme}/*.scss`], compileThemeCss(theme));
    });
}

export { watchFiles as watch };
export default series(css, minifyjs, parallel(...cssTasks));
