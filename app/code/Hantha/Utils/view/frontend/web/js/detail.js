define(["jquery"], function ($) {
    $(window).scroll(() => {
        if ($(window).scrollTop() > 168) {
            $("body").addClass("scrolling");
        } else {
            $("body").removeClass("scrolling");
        }
    });
    /* ++++++++++++ */

    var $accordions, index1;

    const PADDING = 150;
    const DOMloaded = async () => {
        doInit();
        addEventListener("resize", handleResize);
    };

    var doInit = () => {
        console.log("test doInit");
        let $accs = document.querySelectorAll(".accordion-element");
        if ($accs.length <= 0) return;
        $accordions = [];
        index = 1;
        $accs.forEach(function ($accordion) {
            $accordion.setAttribute("data-index", index++);
            $accordion.addEventListener("click", handleAccordionClick);
            $accordions.push($accordion);
        });
        var hash = window.location.hash.substr(1);
        if (hash) {
            document
                .querySelector('[data-index="' + hash + '"] .accordion-title')
                .click();
        } else {
            document.querySelector('[data-index="1"] .accordion-title').click();
        }
    };

    function handleAccordionClick(e) {
        var $target = e.target.closest(".accordion-title");
        if (!$target) return;

        $target = $target.parentElement;

        const opened = $target.classList.contains("open");
        setAccordionContentSize($target.querySelector(".accordion-content"));

        Array.from($target.parentElement.children).forEach(function (
            $accordionElement
        ) {
            $accordionElement.classList.remove("open");
        });
        if (!opened) $target.classList.add("open");
        var temp = document.querySelector(
            ".mask.mask_accordion .accordion-element.open"
        );
        var temp1 = document.querySelector(".mask.mask_accordion");
        // setTimeout(function () {
        // 	if (temp && !isInView(temp)) {
        // 		scrollToAnimated(temp.offsetTop - 150 + temp1.offsetTop, 800)
        // 	}
        // }, 300)
    }

    function setCurrentAccordionSize() {
        $accordions.forEach(function ($accordion) {
            $accordion
                .querySelectorAll(".open .accordion-content")
                .forEach(setAccordionContentSize);
        });
    }

    function setAccordionContentSize($accordionContent) {
        $accordionContent.style.maxHeight =
            $accordionContent.scrollHeight + PADDING + "px";
    }

    var resizeID = 0;

    function handleResize() {
        if (!$accordions || $accordions.length <= 0) return;
        resizeID++;
        delayResize(resizeID);
    }

    function delayResize(rID) {
        setTimeout(function () {
            if (resizeID === rID) setCurrentAccordionSize();
        }, 500);
    }
    $(document).ready(function () {
        DOMloaded();
    });
    return;
});
