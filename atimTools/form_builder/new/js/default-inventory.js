$$(function () {
    function initial() {
        $$("#tools-inventory #aliquot-sample").html("Keep the mouse pointer on top of a <b>Sample</b> item.")

        $$('#tools-inventory span.minus').click(function () {
            $$(this).siblings("ul").children("li").toggle(200);
            $$(this).css("display", "none");
            $$(this).siblings(".plus").css("display", "inline-block");
        });

        $$('#tools-inventory span.plus').click(function () {
            $$(this).siblings("ul").children("li").toggle(200);
            $$(this).css("display", "none");
            $$(this).siblings(".minus").css("display", "inline-block");
        });

        $$('#tools-inventory span.delete').click(function () {
            deleteButton = $$(this);
            text = deleteButton.closest("li").children("div").eq(0).text();
            undoButton = '<span class="undo ui-icon ui-icon-arrowreturnthick-1-w display-none" title="For readding the &quot;peritoneal wash&quot; in all the samples." style="display: inline-block;"></span>';
            deleteDialogue = 
                    "<div>" +
                        "All the " + text + " will be deleted, but you can undo this action by clicking on " + undoButton + " Button."+
                    "</div>";

            $$(deleteDialogue).dialog({
                dialogClass: "no-close",
                modal: true,
                title: "The title text for delete.",
                buttons: [
                    {
                        text: "OK",
                        click: function () {
                            $$(this).dialog("close");
                            dataId = deleteButton.siblings("input[type='checkbox']").attr('data-id');
                            $$("[data-id=" + dataId + "]").each(function () {
                                $this = $$(this);
                                $this.siblings("div").css("text-decoration", "line-through");
                                $this.siblings(".undo").css("display", "inline-block");
                                $this.siblings(".delete").css("display", "none");
                                $this.siblings(".plus").css("display", "none");
                                $this.siblings(".minus").css("display", "none");
                                $this.siblings(".empty").css("display", "inline-block");
                                $this.siblings("ul").children("li").css("display", "none");
                            });
                            preview("#sample", "#preview-sample");

                        }
                    }, {
                        text: "Cancel",
                        click: function () {
                            $$(this).dialog("close");
                        }
                    }
                ]
            });
        });

        $$('#tools-inventory span.undo').click(function () {
            dataId = $$(this).siblings("input[type='checkbox']").attr('data-id');
            $$("[data-id=" + dataId + "]").each(function () {
                $this = $$(this);
                $this.siblings("div").css("text-decoration", "none");
                $this.siblings(".delete").css("display", "inline-block");
                $this.siblings(".undo").css("display", "none");
                if ($this.siblings("ul").children("li").length !== 0) {
                    $this.siblings("span.minus").css("display", "none");
                    $this.siblings("span.plus").css("display", "inline-block");
                    $this.siblings("span.empty").css("display", "none");
                }
            });
            preview("#tools-inventory #sample", "#tools-inventory #preview-sample");
        });

        $$("#tools-inventory input.check-box").change(function () {
            $this = $$(this);
            if (this.checked) {
                if ($this.siblings("ul").children("li").length !== 0) {
                    $this.siblings("span.empty").css("display", "none");
                    $this.siblings("span.plus").css("display", "inline-block");
                }
            } else {
                if ($this.siblings("ul").children("li").length !== 0) {
                    $this.siblings("ul").children("li").css("display", "none");
                    $this.siblings("span.minus").css("display", "none");
                    $this.siblings("span.plus").css("display", "none");
                    $this.siblings("span.empty").css("display", "inline-block");
                }
            }
            preview("#sample", "#preview-sample");
        });

        $$("#tools-inventory #create-query").click(createQuery);

        $$("#tools-inventory span.delete").each(function () {
            $this = $$(this);
            text = $this.siblings("div").text();
            $this.attr("title", "For deleting the \"" + text + "\" in all the samples (Can be retrievable).");
        });

        $$("#tools-inventory span.undo").each(function () {
            $this = $$(this);
            text = $this.siblings("div").text();
            $this.attr("title", "For readding the \"" + text + "\" in all the samples.");
        });

        $$("#tools-inventory span.plus").each(function () {
            $this = $$(this);
            $this.attr("title", "See more sub samples.");
        });

        function preview(scope, scopePreview) {
            scope = $$(scope);
            scopePreview = $$(scopePreview);
            scopePreview.html("<div></div>");
            scopePreview.children("div").eq(0).html(scope.children("ul").clone());
            scopePreview.find("input[type=checkbox]:not(:checked)").closest("li").remove()
            scopePreview.find(".delete, .undo, input[type=checkbox], .plus, .minus, .empty").remove();
            scopePreview.find("div[style*='line-through']").closest("li").remove();
            scopePreview.find("li").css("display", "list-item");
            scopePreview.find("li").removeClass('display-none');
            scopePreview.children("div").eq(0).find("div").each(function () {
                $this = $$(this);
                $this.replaceWith($this.text());
            });
            scopePreview.children("div").eq(0).jstree();
        }

        function sqlHelper(scope) {
            $$("div#sql-helper").remove();
            scopePreview = $$("<div class = 'display-none' id='sql-helper'></div>");
            $$("body").append(scopePreview);

            scope = $$(scope);
            scopePreview.html(scope.children("ul").clone());
            scopePreview.find("input[type=checkbox]:not(:checked)").closest("li").remove()
            scopePreview.find(".delete, .undo, input[type=checkbox], .plus, .minus, .empty").remove();
            scopePreview.find("div[style*='line-through']").closest("li").remove();
            scopePreview.find("li").css("display", "list-item");
            scopePreview.find("li").removeClass('display-none');
            scopePreview.find("div").each(function () {
                $this = $$(this);
                $this.replaceWith($this.text());
            });
        }

        function createQuery() {
            var sample = new Array();
            var aliquot = new Array();
            var realiquot = new Array();
            $$("#sample li").each(function () {
                $this = $$(this);

                checked = ($this.children("input[type='checkbox']").is(':checked') && $this.children("div[style*='line-through']").length === 0);
                parents = $this.parents("li");
                for (i = 0; checked && i < parents.length; i++) {
                    if ($$(parent).children("div[style*='line-through']").length !== 0) {
                        checked = false;
                    }
                }

                id = $this.attr('data-row-id');
                parentId = $this.attr('data-parent-id');
                derivatedId = $this.attr('data-id');

                sample.push(
                        '{' +
                        '"id": ' + id +
                        ', "parent_id": ' + parentId +
                        ', "children_id": ' + derivatedId +
                        ', "flag_active": ' + checked +
                        '}'
                        );
            });

            $$("#aliquot li.aliquot").each(function () {
                $this = $$(this);
                checked = !$this.hasClass("disable");
                id = $this.attr("data-id");
                aliquot.push(
                        '{' +
                        '"id": ' + id +
                        ', "flag_active": ' + checked +
                        '}'
                        );
            });

            $$("#aliquot li.re-aliquot").each(function () {
                $this = $$(this);
                checked = !$this.hasClass("disable");
                id = $this.attr("data-id");
                realiquot.push(
                        '{' +
                        '"id": ' + id +
                        ', "flag_active": ' + checked +
                        '}'
                        );
            });

            sample = sample.filter(function (item, pos) {
                return sample.indexOf(item) === pos;
            });
            json = '{' +
                    '"sample": [' + sample.join(', ') + '],' +
                    '"aliquot": [' + aliquot.join(', ') + '],' +
                    '"realiquot": [' + realiquot.join(', ') + ']' +
                    '}';

            $$.post("new/sqlGenerator-inventory.php", "json=" + json, function (data) {
                $$("#tools-inventory #out").val(data);
                if (data !== "") {
                    $$("#tools-inventory #copy-queries").removeAttr("disabled");
                } else {
                    $$("#tools-inventory #copy-queries").attr("disabled", "disabled");
                }
            });


        }

        $$("#copy-queries").click(function () {
            $$("#out").select();
            document.execCommand("Copy");
        });

        preview("#sample", "#preview-sample");

        $$("#tools-inventory").tooltip({show: {delay: 1000}});

        $$("div.aliquot-display-hover").hover(function () {
            $this = $$(this);
            timeHover = setTimeout(function () {
                id = $this.siblings("input.check-box").attr("data-id");
                aliquot = $$("#aliquot").find("li.sample[data-id ='" + id + "']").eq(0).clone();
                $$("#aliquot-sample").html("");
                $$("#aliquot-sample").append(aliquot);
                show("#aliquot-sample");
            }, 300);
        }, function () {
            clearTimeout(timeHover);
        });

        function show(scope) {
            $scope = $$(scope);
            $scope.find(".sample").find("li").prepend("<input type='checkbox' class = 'check-box' checked>");
            $scope.find('.disable').children('input.check-box').prop('checked', false);
            $scope.find('.disable').removeClass('disable');

            $scope.find("li.aliquot>input.check-box").each(function () {
                $this = $$(this);
                if ($this.is(":not(:checked)")) {
                    $this.siblings('ul').find("input.check-box").each(function () {
                        $$(this).prop('checked', false);
                        $$(this).prop('disabled', true);
                    });
                    id = $this.closest("li").attr("data-id");
                    $this.closest("ul").find(".re-aliquot[data-child-id='" + id + "'] input.check-box").each(function () {
                        $$(this).prop('checked', false);
                        $$(this).prop('disabled', true);
                    });
                }
            });
            $scope.find(".sample").find("li input.check-box").change(function () {
                $this = $$(this);
                div = $this.siblings("div");
                if (this.checked) {
                    $this.siblings('ul').find("input.check-box").each(function () {
                        id = $$(this).closest("li").attr("data-child-id");
                        if ($scope.find("li.aliquot[data-id='" + id + "']>input.check-box").is(":checked")) {
                            $$(this).prop('disabled', false);
                        }
                    });
                    id = $this.closest("li").attr("data-id");
                    $this.closest("ul").find(".re-aliquot[data-child-id='" + id + "'] input.check-box").each(function () {
                        if ($$(this).closest("li.aliquot").children("input.check-box").is(":checked")) {
                            $$(this).prop('disabled', false);
                        }
                    });
                } else {
                    if ($this.closest("li").hasClass("aliquot")) {
                        $this.siblings('ul').find("input.check-box").each(function () {
                            $$(this).prop('checked', false);
                            $$(this).prop('disabled', true);
                        });
                        id = $this.closest("li").attr("data-id");
                        $this.closest("ul").find(".re-aliquot[data-child-id='" + id + "'] input.check-box").each(function () {
                            $$(this).prop('checked', false);
                            $$(this).prop('disabled', true);
                        });
                    }
                }
                id = $scope.find("li.sample").attr('data-id');
                $aliquot = $$("#aliquot").find("li.sample[data-id ='" + id + "']").eq(0);
                $scope.find("li.sample input.check-box").each(function (index) {
                    $this = $$(this);
                    $that = $aliquot.find("li").eq(index);
                    if ($this.is(":checked")) {
                        $that.removeClass("disable");
                    } else {
                        $that.addClass("disable");
                    }
                });
            });
        }
    }

    function initialAliquots(scope) {
        $scope = $$(scope);
        $scope.find('.no-display').remove();
    }

    $$("document").ready(function () {
        $$.get('new/samples.php', {data: 'sample'}, function (data) {
            $$("#sample").html(data);
            initial();
        });
        $$.get('new/samples.php', {data: 'aliquot'}, function (data) {
            $$("#aliquot").html(data);
            initialAliquots("#aliquot");
        });
    });
});