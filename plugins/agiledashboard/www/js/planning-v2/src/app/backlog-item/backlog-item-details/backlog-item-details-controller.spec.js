import angular from "angular";
import "angular-mocks";

import details_module from "./backlog-item-details.js";
import BaseBacklogItemDetailsController from "./backlog-item-details-controller.js";

describe("BacklogItemDetailsController -", function() {
    var $q,
        $scope,
        BacklogItemDetailsController,
        BacklogItemCollectionService,
        NewTuleapArtifactModalService,
        BacklogItemService;

    beforeEach(function() {
        angular.mock.module(details_module);

        angular.mock.inject(function(
            _$q_,
            $rootScope,
            $controller,
            _BacklogItemCollectionService_,
            _BacklogItemService_,
            _NewTuleapArtifactModalService_
        ) {
            $q = _$q_;
            $scope = $rootScope.$new();

            BacklogItemCollectionService = _BacklogItemCollectionService_;
            spyOn(BacklogItemCollectionService, "refreshBacklogItem");

            BacklogItemService = _BacklogItemService_;
            spyOn(BacklogItemService, "getBacklogItem");
            spyOn(BacklogItemService, "getBacklogItemChildren");
            spyOn(BacklogItemService, "removeAddBacklogItemChildren");

            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            spyOn(NewTuleapArtifactModalService, "showCreation");

            BacklogItemDetailsController = $controller(BaseBacklogItemDetailsController, {
                BacklogItemCollectionService: BacklogItemCollectionService,
                BacklogItemService: BacklogItemService,
                NewTuleapArtifactModalService: NewTuleapArtifactModalService
            });
        });
    });

    describe("showAddChildModal() -", () => {
        let event, item_type;
        beforeEach(() => {
            event = jasmine.createSpyObj("Click event", ["preventDefault"]);
            item_type = { id: 7 };
            BacklogItemDetailsController.backlog_item = {
                id: 53,
                has_children: true,
                children: {
                    loaded: true,
                    data: [{ id: 352 }]
                },
                updating: false
            };
            BacklogItemCollectionService.items[53] = BacklogItemDetailsController.backlog_item;
        });

        it("Given an event and an item type, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", () => {
            BacklogItemDetailsController.showAddChildModal(event, item_type);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(
                7,
                BacklogItemDetailsController.backlog_item.id,
                jasmine.any(Function)
            );
        });

        describe("callback -", () => {
            let artifact;
            beforeEach(() => {
                NewTuleapArtifactModalService.showCreation.and.callFake((a, b, callback) =>
                    callback(207)
                );
                artifact = {
                    backlog_item: {
                        id: 207
                    }
                };
            });

            it("When the artifact modal calls its callback, then the artifact will be appended to the current backlog item's children using REST, it will be retrieved from the server, added to the items collection and appended to the current backlog item's children array", () => {
                BacklogItemService.getBacklogItem.and.returnValue($q.when(artifact));
                BacklogItemService.removeAddBacklogItemChildren.and.returnValue($q.when());

                BacklogItemDetailsController.showAddChildModal(event, item_type);
                $scope.$apply();

                expect(BacklogItemService.removeAddBacklogItemChildren).toHaveBeenCalledWith(
                    undefined,
                    53,
                    [207]
                );
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(207);
                expect(BacklogItemCollectionService.items[207].id).toEqual(207);
                expect(BacklogItemCollectionService.items[207].parent.id).toEqual(53);
                expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(53);
                const children_ids = BacklogItemDetailsController.backlog_item.children.data.map(
                    ({ id }) => id
                );
                expect(children_ids).toEqual([352, 207]);
            });

            it("Given that the current backlog item did not have children, when the new artifact modal calls its callback, then the artifact will be appended to the current backlog item's children and the children will be marked as loaded", () => {
                BacklogItemDetailsController.backlog_item.children = {
                    loaded: false,
                    data: []
                };
                BacklogItemDetailsController.backlog_item.has_children = false;
                BacklogItemService.getBacklogItem.and.returnValue($q.when(artifact));
                BacklogItemService.removeAddBacklogItemChildren.and.returnValue($q.when());

                BacklogItemDetailsController.showAddChildModal(event, item_type);
                $scope.$apply();

                const children_ids = BacklogItemDetailsController.backlog_item.children.data.map(
                    ({ id }) => id
                );
                expect(children_ids).toEqual([207]);
                expect(BacklogItemDetailsController.backlog_item.children.loaded).toBeTruthy();
            });
        });
    });

    describe("canBeAddedToBacklogItemChildren() - ", function() {
        it("Given that the current backlog item had no child, it appends the newly created child", function() {
            BacklogItemDetailsController.backlog_item = {
                has_children: false,
                children: {}
            };
            var created_item = {
                id: 8
            };

            var result = BacklogItemDetailsController.canBeAddedToChildren(created_item.id);

            expect(result).toBeTruthy();
        });

        it("Given that the current backlog item had already loaded children, it appends the newly created child if not already present", function() {
            BacklogItemDetailsController.backlog_item = {
                has_children: true,
                children: {
                    loaded: true,
                    data: [{ id: 1 }, { id: 2 }, { id: 3 }]
                }
            };
            var created_item = {
                id: 8
            };

            var result = BacklogItemDetailsController.canBeAddedToChildren(created_item.id);

            expect(result).toBeTruthy();
        });

        it("Given that the current backlog item had already loaded children, it doesn't append the newly created child if already present", function() {
            BacklogItemDetailsController.backlog_item = {
                has_children: true,
                children: {
                    loaded: true,
                    data: [{ id: 1 }, { id: 2 }, { id: 8 }]
                }
            };
            var created_item = {
                id: 8
            };

            var result = BacklogItemDetailsController.canBeAddedToChildren(created_item.id);

            expect(result).toBeFalsy();
        });

        it("Given that the current backlog item didn't have already loaded children, it doesn't append the newly created child", function() {
            BacklogItemDetailsController.backlog_item = {
                has_children: true,
                children: {
                    loaded: false,
                    data: []
                }
            };
            var created_item = {
                id: 8
            };

            expect(BacklogItemDetailsController.canBeAddedToChildren(created_item.id)).toBeFalsy();
        });
    });
});
